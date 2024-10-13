<?php

namespace App\Modules\Dashboard\Infrastructure\Http\Controllers;

use App\Modules\Dashboard\Contracts\Actions\FindServiceStatActionInterface;
use App\Modules\Dashboard\Infrastructure\Http\Resources\ServiceStatResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class ServiceStatController
{
    public function __construct(private FindServiceStatActionInterface $serviceStatService)
    {
    }

    #[OaListItemTypeAttribute(ServiceStatResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return ServiceStatResource::collection(
            $this->serviceStatService->handle()
        );
    }
}
