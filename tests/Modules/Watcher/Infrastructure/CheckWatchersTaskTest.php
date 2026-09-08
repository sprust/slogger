<?php

namespace Tests\Modules\Watcher\Infrastructure;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceBufferCountAction;
use App\Modules\Watcher\Domain\Actions\Mutations\CheckWatcherAction;
use App\Modules\Watcher\Domain\Actions\Mutations\DeleteOrphanWatcherTimelinesAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatchersAction;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Tasks\CheckWatchersTask;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SConcur\Laravel\Tasks\TaskPoolLogger;
use SConcur\Laravel\Tasks\TickResultEnum;
use Tests\Modules\Watcher\WatcherFactoryTrait;

/**
 * The pass is once a minute; the task ticks more often than that and watches the minute
 * itself, so a tick delayed by a busy pool still serves the minute it belongs to.
 */
class CheckWatchersTaskTest extends TestCase
{
    use WatcherFactoryTrait;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Unlike CronTask, which starts from "this minute has already run": re-running a pass
     * costs nothing — the cooldown decides whether anything is said — and waiting up to a
     * minute after a deploy to look at anything is worse.
     */
    public function testTheFirstTickLooksStraightAway(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 7, 12, 0, 20));

        $checkAction = $this->createMock(CheckWatcherAction::class);
        $checkAction->expects($this->once())->method('handle');

        $this->assertSame(TickResultEnum::Worked, $this->task($checkAction)->tick());
    }

    public function testTheSameMinuteIsNotLookedAtTwice(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 7, 12, 0, 20));

        $checkAction = $this->createMock(CheckWatcherAction::class);
        $checkAction->expects($this->once())->method('handle');

        $task = $this->task($checkAction);

        $this->assertSame(TickResultEnum::Worked, $task->tick());

        Carbon::setTestNow(Carbon::create(2026, 9, 7, 12, 0, 55));

        $this->assertSame(TickResultEnum::Idle, $task->tick());
    }

    public function testTheNextMinuteIsLookedAtAgain(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 7, 12, 0, 20));

        $checkAction = $this->createMock(CheckWatcherAction::class);
        $checkAction->expects($this->exactly(2))->method('handle');

        $task = $this->task($checkAction);

        $task->tick();

        Carbon::setTestNow(Carbon::create(2026, 9, 7, 12, 1, 5));

        $this->assertSame(TickResultEnum::Worked, $task->tick());
    }

    public function testADisabledWatcherIsNotChecked(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 7, 12, 0, 20));

        $checkAction = $this->createMock(CheckWatcherAction::class);
        $checkAction->expects($this->never())->method('handle');

        $this->assertSame(
            TickResultEnum::Idle,
            $this->task($checkAction, watchers: [$this->disabled()])->tick()
        );
    }

    /**
     * Lines are swept by every watcher that exists, disabled ones included — otherwise
     * switching a watcher off would delete the history it comes back to.
     */
    public function testTheSweepCountsDisabledWatchersAsExisting(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 7, 12, 0, 20));

        $sweep = $this->createMock(DeleteOrphanWatcherTimelinesAction::class);
        $sweep->expects($this->once())->method('handle')->with([1, 2]);

        $this->task(
            $this->createMock(CheckWatcherAction::class),
            watchers: [$this->enabled(1), $this->disabled(2)],
            sweep: $sweep
        )->tick();
    }

    /**
     * A Mongo that will not answer must not stop the watchers that never asked it
     * anything, and must not be reported to the ones that did as an empty buffer.
     */
    public function testABufferSizeThatCouldNotBeReadDoesNotStopThePass(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 7, 12, 0, 20));

        $bufferAction = $this->createMock(FindTraceBufferCountAction::class);
        $bufferAction->method('handle')->willThrowException(new RuntimeException('mongo is away'));

        $checkAction = $this->createMock(CheckWatcherAction::class);
        $checkAction->expects($this->once())
            ->method('handle')
            ->with(
                $this->anything(),
                $this->callback(
                    static fn(WatcherCheckContextObject $context): bool => is_null($context->bufferCount)
                )
            );

        $this->assertSame(
            TickResultEnum::Worked,
            $this->task($checkAction, bufferAction: $bufferAction)->tick()
        );
    }

    /**
     * @param WatcherObject[]|null $watchers
     */
    private function task(
        CheckWatcherAction $checkAction,
        ?array $watchers = null,
        ?DeleteOrphanWatcherTimelinesAction $sweep = null,
        ?FindTraceBufferCountAction $bufferAction = null
    ): CheckWatchersTask {
        $findWatchers = $this->createMock(FindWatchersAction::class);
        $findWatchers->method('handle')->willReturn($watchers ?? [$this->enabled(1)]);

        if (is_null($bufferAction)) {
            $bufferAction = $this->createMock(FindTraceBufferCountAction::class);
            $bufferAction->method('handle')->willReturn(0);
        }

        return new CheckWatchersTask(
            $findWatchers,
            $bufferAction,
            $checkAction,
            $sweep ?? $this->createMock(DeleteOrphanWatcherTimelinesAction::class),
            $this->createMock(TaskPoolLogger::class)
        );
    }

    private function enabled(int $id = 1): WatcherObject
    {
        return $this->watcher(WatcherTypeEnum::BufferOverflow, new BufferOverflowSettingsObject(), id: $id);
    }

    private function disabled(int $id = 1): WatcherObject
    {
        $watcher = $this->enabled($id);

        return new WatcherObject(
            id: $watcher->id,
            name: $watcher->name,
            type: $watcher->type,
            enabled: false,
            cooldownSeconds: $watcher->cooldownSeconds,
            settings: $watcher->settings,
            match: null,
            collectSince: null,
            lastCheckedAt: null,
            lastTriggeredAt: null,
            createdAt: $watcher->createdAt,
            updatedAt: $watcher->updatedAt
        );
    }
}
