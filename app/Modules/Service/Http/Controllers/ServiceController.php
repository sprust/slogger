<?php

namespace App\Modules\Service\Http\Controllers;

use App\Modules\Service\Http\Resources\ServiceResource;
use App\Modules\Service\Services\ServiceService;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class ServiceController
{
    public function __construct(private ServiceService $service)
    {
    }

    #[OaListItemTypeAttribute(ServiceResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return ServiceResource::collection($this->service->find());
    }
}
