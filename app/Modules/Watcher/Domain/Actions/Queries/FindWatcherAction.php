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

    public function handle(int $id): ?WatcherObject
    {
        $dto = $this->watcherRepository->findById($id);

        return is_null($dto) ? null : $this->watcherFactory->make($dto);
    }
}
