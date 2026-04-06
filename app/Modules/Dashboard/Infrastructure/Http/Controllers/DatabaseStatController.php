<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Controllers;

use App\Modules\Dashboard\Domain\Actions\FindDatabaseStatCacheAction;
use App\Modules\Dashboard\Domain\Exceptions\DatabaseStatCacheNotFoundException;
use App\Modules\Dashboard\Infrastructure\Http\Resources\DatabaseResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class DatabaseStatController
{
    public function __construct(
        private FindDatabaseStatCacheAction $findDatabaseStatCacheAction
    ) {
    }

    /**
     * @throws DatabaseStatCacheNotFoundException
     */
    #[OaListItemTypeAttribute(DatabaseResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return DatabaseResource::collection(
            $this->findDatabaseStatCacheAction->handle()
        );
    }
}
