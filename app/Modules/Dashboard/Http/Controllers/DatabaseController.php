<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Modules\Dashboard\Http\Resources\DatabaseResource;
use App\Modules\Dashboard\Repositories\Database\DatabaseRepositoryInterface;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class DatabaseController
{
    public function __construct(private DatabaseRepositoryInterface $repository)
    {
    }

    #[OaListItemTypeAttribute(DatabaseResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return DatabaseResource::collection(
            $this->repository->find()->getItems()
        );
    }
}
