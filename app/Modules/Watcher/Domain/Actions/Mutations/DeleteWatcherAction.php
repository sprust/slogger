<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Mutations;

use App\Modules\Watcher\Repositories\WatcherRepository;

/**
 * Removes a watcher, and nothing else.
 *
 * Soft, so that the incidents it left behind still have a name to show against. And only
 * the row: its line, its incidents and their events all live in Mongo under a TTL, which
 * retires them on its own — deleting them here would take away the history of a problem
 * at the moment somebody tidied up the watcher that found it.
 */
readonly class DeleteWatcherAction
{
    public function __construct(
        private WatcherRepository $watcherRepository
    ) {
    }

    public function handle(int $watcherId): void
    {
        $this->watcherRepository->delete($watcherId);
    }
}
