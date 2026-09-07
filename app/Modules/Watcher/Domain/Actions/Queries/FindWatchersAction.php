<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Queries;

use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Repositories\WatcherRepository;

readonly class FindWatchersAction
{
    public function __construct(
        private WatcherRepository $watcherRepository
    ) {
    }

    /**
     * @return WatcherObject[]
     */
    public function handle(?bool $enabled = null): array
    {
        return $this->watcherRepository->find($enabled);
    }
}
