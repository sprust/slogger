<?php

namespace App\Modules\Dashboard\Framework\Http\Controllers;

use App\Modules\Dashboard\Domain\Actions\FindServiceStatAction;
use App\Modules\Dashboard\Framework\Http\Resources\ServiceStatResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class ServiceStatController
{
    public function __construct(private FindServiceStatAction $serviceStatService)
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
