<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Queries;

use App\Modules\Watcher\Domain\Services\WatcherFactory;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Repositories\Dto\WatcherDto;
use App\Modules\Watcher\Repositories\WatcherRepository;

readonly class FindWatchersAction
{
    public function __construct(
        private WatcherRepository $watcherRepository,
        private WatcherFactory $watcherFactory
    ) {
    }

    /**
     * @return WatcherObject[]
     */
    public function handle(?bool $enabled = null): array
    {
        // Rows of a type this build cannot read are dropped rather than raised: the pass
        // that calls this reads every watcher before it checks any of them, and one row
        // nobody can make sense of is no reason to check none of them.
        return array_values(
            array_filter(
                array_map(
                    fn(WatcherDto $dto): ?WatcherObject => $this->watcherFactory->make($dto),
                    $this->watcherRepository->find($enabled)
                )
            )
        );
    }
}
