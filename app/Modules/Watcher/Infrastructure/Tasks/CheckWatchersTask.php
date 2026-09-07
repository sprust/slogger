<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Tasks;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceBufferCountAction;
use App\Modules\Watcher\Domain\Actions\Mutations\CheckWatcherAction;
use App\Modules\Watcher\Domain\Actions\Mutations\DeleteOrphanWatcherTimelinesAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatchersAction;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherObject;
use Illuminate\Support\Carbon;
use SConcur\Laravel\Tasks\TaskInterface;
use SConcur\Laravel\Tasks\TaskPoolLogger;
use SConcur\Laravel\Tasks\TickResultEnum;
use SConcur\WaitGroup;
use Throwable;

/**
 * Looks at every enabled watcher, once a minute.
 *
 * A task of the pool rather than a scheduled job, and the difference is not stylistic.
 * The scheduler itself runs from this pool — App\Services\Tasks\CronTask calls
 * schedule:run — so a job's path would be pool, schedule:run, RabbitMQ, consumer pool,
 * job: four links where this is one, and two of them are exactly what a watcher must not
 * depend on. A stalled queue would stop the watchers during the incident they exist for,
 * and one of the five types watches the buffer, which fills for the same reasons a queue
 * stalls.
 *
 * The pool also makes it the only writer: it holds an flock the kernel releases even on
 * SIGKILL, so two passes cannot open two incidents about one thing.
 */
class CheckWatchersTask implements TaskInterface
{
    public const string NAME = 'watchers';

    /**
     * The minute the last pass ran, watched rather than slept through, so a tick delayed
     * by a busy pool still serves the minute it belongs to.
     *
     * Null at first on purpose, unlike CronTask: a restart re-running a pass costs
     * nothing — the cooldown decides whether anything is said — and waiting up to a minute
     * after a deploy to look at anything is worse.
     */
    private ?int $previousMinute = null;

    public function __construct(
        private readonly FindWatchersAction $findWatchersAction,
        private readonly FindTraceBufferCountAction $findBufferCountAction,
        private readonly CheckWatcherAction $checkWatcherAction,
        private readonly DeleteOrphanWatcherTimelinesAction $deleteOrphanTimelinesAction,
        private readonly TaskPoolLogger $logger
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function tick(): TickResultEnum
    {
        $now = Carbon::now();

        if ($this->previousMinute === $now->minute) {
            return TickResultEnum::Idle;
        }

        $this->previousMinute = $now->minute;

        return $this->pass($now);
    }

    private function pass(Carbon $now): TickResultEnum
    {
        // Every watcher, not only the enabled ones: the disabled ones still own their
        // lines, and the sweep below decides what to remove by this list.
        $watchers = $this->findWatchersAction->handle();

        $this->deleteOrphanTimelinesAction->handle(
            array_map(static fn(WatcherObject $watcher): int => $watcher->id, $watchers)
        );

        $enabled = array_values(
            array_filter($watchers, static fn(WatcherObject $watcher): bool => $watcher->enabled)
        );

        if (!count($enabled)) {
            return TickResultEnum::Idle;
        }

        $context = new WatcherCheckContextObject(
            now: $now,
            bufferCount: $this->bufferCount()
        );

        // A coroutine each. One watcher's turn is a read of its own line and, rarely, a
        // write; they share nothing, so waiting for them in turn would only add up
        // latencies.
        $waitGroup = WaitGroup::create();

        foreach ($enabled as $watcher) {
            $waitGroup->add(fn() => $this->checkWatcherAction->handle($watcher, $context));
        }

        $waitGroup->waitAll();

        return TickResultEnum::Worked;
    }

    /**
     * The buffer's size, read once for the whole pass, or null if it could not be read.
     *
     * Null rather than zero, and caught rather than raised: a Mongo that will not answer
     * must not take down the watchers that never asked it anything, and it must not be
     * reported to the ones that did as an empty buffer.
     */
    private function bufferCount(): ?int
    {
        try {
            return $this->findBufferCountAction->handle();
        } catch (Throwable $exception) {
            $this->logger->log(self::NAME, 'failed to read the buffer size: ' . $exception->getMessage());

            return null;
        }
    }
}
