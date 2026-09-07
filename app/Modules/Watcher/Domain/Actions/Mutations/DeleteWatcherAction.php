<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Mutations;

use App\Modules\Watcher\Repositories\WatcherRepository;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;

/**
 * Removes a watcher, its line, and — through the foreign keys — its incidents and their
 * events.
 */
readonly class DeleteWatcherAction
{
    public function __construct(
        private WatcherRepository $watcherRepository,
        private WatcherTimelineRepository $timelineRepository
    ) {
    }

    public function handle(int $watcherId): void
    {
        $this->watcherRepository->delete($watcherId);

        // After the row, not before: the receiver reloads its list every half minute, and
        // while the watcher is still there it may write the line again. Removing the row
        // first makes that window as short as the reload, and the orphan sweep of the
        // check pass covers what slips through.
        $this->timelineRepository->delete($watcherId);
    }
}
