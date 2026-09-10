<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Queries;

use App\Modules\Watcher\Domain\Services\WatcherFactory;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Repositories\WatcherRepository;

readonly class FindWatcherAction
{
    public function __construct(
        private WatcherRepository $watcherRepository,
        private WatcherFactory $watcherFactory
    ) {
    }

    /**
     * @param bool $withTrashed a deleted watcher still answers, for the incidents it left
     *                          behind
     */
    public function handle(int $id, bool $withTrashed = false): ?WatcherObject
    {
        $dto = $this->watcherRepository->findById($id, $withTrashed);

        return is_null($dto) ? null : $this->watcherFactory->make($dto);
    }
}
