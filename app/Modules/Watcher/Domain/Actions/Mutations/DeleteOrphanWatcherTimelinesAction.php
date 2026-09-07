<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Mutations;

use App\Modules\Watcher\Repositories\WatcherTimelineRepository;

/**
 * Removes lines whose watcher no longer exists.
 *
 * A safety net, not the main path: deleting a watcher deletes its line. But the receiver
 * may still hold buckets for it and write them a moment later, and the collection has no
 * TTL — a live watcher's line must never expire — so a document recreated after the fact
 * would stay for good.
 */
readonly class DeleteOrphanWatcherTimelinesAction
{
    public function __construct(
        private WatcherTimelineRepository $timelineRepository
    ) {
    }

    /**
     * @param int[] $watcherIds every watcher that exists, enabled or not
     */
    public function handle(array $watcherIds): void
    {
        $this->timelineRepository->deleteExcept($watcherIds);
    }
}
