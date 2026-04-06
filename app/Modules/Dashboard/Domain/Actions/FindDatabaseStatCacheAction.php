<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Domain\Exceptions\DatabaseStatCacheNotFoundException;
use App\Modules\Dashboard\Entities\DatabaseStatCacheObject;
use App\Modules\Dashboard\Repositories\DatabaseStatCacheRepository;

readonly class FindDatabaseStatCacheAction
{
    public function __construct(
        private DatabaseStatCacheRepository $cacheRepository
    ) {
    }

    /**
     * @throws DatabaseStatCacheNotFoundException
     */
    public function handle(): DatabaseStatCacheObject
    {
        $result = $this->cacheRepository->find();

        if (is_null($result)) {
            throw new DatabaseStatCacheNotFoundException();
        }

        return $result;
    }
}
