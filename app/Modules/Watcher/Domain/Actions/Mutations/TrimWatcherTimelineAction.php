<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Mutations;

use App\Modules\Watcher\Entities\Settings\HasTraceFilterInterface;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;
use Illuminate\Support\Carbon;

/**
 * Cuts off the part of a watcher's line it can no longer see.
 *
 * The receiver caps the array too, but at three hours for everybody. This is the cut by
 * what the watcher was actually configured to look at, which is usually minutes — and it
 * is what keeps the document small enough that reading a line stays one cheap findOne.
 */
readonly class TrimWatcherTimelineAction
{
    /**
     * How much beyond the configured depth is kept.
     *
     * A window is measured back from a moment that already lags behind now, and the
     * settings can change between passes. Trimming exactly to the depth would sometimes
     * remove a bucket the very next check still wanted.
     */
    private const int SLACK_MINUTES = 5;

    public function __construct(
        private WatcherTimelineRepository $timelineRepository
    ) {
    }

    public function handle(WatcherObject $watcher, Carbon $now): void
    {
        $settings = $watcher->settings;

        // A watcher with no filter has no line: the receiver never writes one for it.
        if (!$settings instanceof HasTraceFilterInterface) {
            return;
        }

        $this->timelineRepository->trim(
            watcherId: $watcher->id,
            before: $now->clone()->subMinutes($settings->timelineDepthMinutes() + self::SLACK_MINUTES)
        );
    }
}
