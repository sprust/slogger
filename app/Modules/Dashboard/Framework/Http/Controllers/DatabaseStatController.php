<?php

namespace App\Modules\Dashboard\Framework\Http\Controllers;

use App\Modules\Dashboard\Domain\Actions\Interfaces\FindDatabaseStatActionInterface;
use App\Modules\Dashboard\Framework\Http\Resources\DatabaseResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class DatabaseStatController
{
    public function __construct(
        private FindDatabaseStatActionInterface $findDatabaseStatAction
    ) {
    }

    #[OaListItemTypeAttribute(DatabaseResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return DatabaseResource::collection(
            $this->findDatabaseStatAction->handle()
        );
    }
}
