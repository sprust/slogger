<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Tasks;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexStatsAction;
use App\Modules\Trace\Infrastructure\Broadcasting\TraceDynamicIndexStatsBroadcast;
use Illuminate\Contracts\Events\Dispatcher;
use SConcur\Laravel\Tasks\TaskInterface;
use SConcur\Laravel\Tasks\TickResultEnum;

/**
 * Reports what the dynamic indexes are doing, so that the panel does not have to ask.
 *
 * A task of its own rather than a few lines inside BuildTraceDynamicIndexesTask, and
 * that separation is the whole point: a tick of that task does not return until its whole
 * batch is built — createIndex() ends in a WaitGroup that waits for every collection — so
 * anything reporting from inside it would only ever speak once the work it is reporting
 * on had finished. The pool runs every task as its own coroutine, so this one keeps
 * ticking while that one is busy.
 *
 * Silence is the resting state. Nothing is published while nothing is being built, which
 * is what keeps this from costing anything on an idle installation — a reading is taken
 * every couple of seconds and thrown away. That reading is two small Mongo operations
 * (an aggregate over the index collection and a currentOp on admin); the poll it replaced
 * made the same two, per open tab, twice a second.
 */
class PublishTraceDynamicIndexStatsTask implements TaskInterface
{
    public const string NAME = 'trace-dynamic-index-stats';

    /** Whether the last snapshot sent described work in progress. */
    private bool $announced = false;

    public function __construct(
        private readonly FindTraceDynamicIndexStatsAction $findStatsAction,
        private readonly Dispatcher $events,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function tick(): TickResultEnum
    {
        $stats = $this->findStatsAction->handle();

        $working = $stats->inProcessCount > 0;

        // Nothing is happening and nothing was said, so there is nothing to take back.
        if (!$working && !$this->announced) {
            return TickResultEnum::Idle;
        }

        $this->announced = $working;

        $this->events->dispatch(new TraceDynamicIndexStatsBroadcast($stats));

        // The closing frame is not work: saying "there is nothing" and then idling is the
        // point of it. Without it the last thing a watcher ever received is the progress
        // of an index that has since finished, and it stays on the screen for ever.
        return $working ? TickResultEnum::Worked : TickResultEnum::Idle;
    }
}
