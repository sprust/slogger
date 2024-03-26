<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Modules\Dashboard\Http\Resources\ServiceStatResource;
use App\Modules\Dashboard\Services\ServiceStat\ServiceStatService;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class ServiceStatController
{
    public function __construct(private ServiceStatService $serviceStatService)
    {
    }

    #[OaListItemTypeAttribute(ServiceStatResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return ServiceStatResource::collection(
            $this->serviceStatService->find()
        );
    }
}
