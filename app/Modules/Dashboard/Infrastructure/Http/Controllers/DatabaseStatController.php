<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Controllers;

use App\Modules\Dashboard\Domain\Actions\FindDatabaseStatCacheAction;
use App\Modules\Dashboard\Domain\Exceptions\DatabaseStatCacheNotFoundException;
use App\Modules\Dashboard\Infrastructure\Http\Resources\DatabaseStatListResource;

readonly class DatabaseStatController
{
    public function __construct(
        private FindDatabaseStatCacheAction $findDatabaseStatCacheAction
    ) {
    }

    /**
     * @throws DatabaseStatCacheNotFoundException
     */
    public function index(): DatabaseStatListResource
    {
        return new DatabaseStatListResource(
            $this->findDatabaseStatCacheAction->handle()
        );
    }
}
