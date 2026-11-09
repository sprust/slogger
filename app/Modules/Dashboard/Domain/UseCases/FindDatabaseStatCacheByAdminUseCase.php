<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\UseCases;

use App\Modules\Dashboard\Domain\Exceptions\DatabaseStatCacheNotFoundException;
use App\Modules\Dashboard\Entities\DatabaseStatCacheObject;
use App\Modules\Dashboard\Domain\Actions\FindDatabaseStatCacheAction;

readonly class FindDatabaseStatCacheByAdminUseCase
{
    public function __construct(private FindDatabaseStatCacheAction $findDatabaseStatCacheAction)
    {
    }

    /**
     * @throws DatabaseStatCacheNotFoundException
     */
    public function handle(): DatabaseStatCacheObject
    {
        return $this->findDatabaseStatCacheAction->handle();
    }
}
