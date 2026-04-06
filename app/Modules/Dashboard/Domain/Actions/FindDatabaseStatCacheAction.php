<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Domain\Exceptions\DatabaseStatCacheNotFoundException;
use App\Modules\Dashboard\Entities\DatabaseStatObject;
use App\Modules\Dashboard\Repositories\DatabaseStatCacheRepository;

readonly class FindDatabaseStatCacheAction
{
    public function __construct(
        private DatabaseStatCacheRepository $cacheRepository
    ) {
    }

    /**
     * @return DatabaseStatObject[]
     *
     * @throws DatabaseStatCacheNotFoundException
     */
    public function handle(): array
    {
        $stats = $this->cacheRepository->find();

        if (is_null($stats)) {
            throw new DatabaseStatCacheNotFoundException();
        }

        return $stats;
    }
}
