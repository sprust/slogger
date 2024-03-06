<?php

namespace App\Modules\Service\Http\Controllers;

use App\Modules\Service\Http\Resources\ServiceDetailResource;
use App\Modules\Service\Http\Resources\ServiceResource;
use App\Modules\Service\Services\ServiceService;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

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

    public function show(int $serviceId): ServiceDetailResource
    {
        $service = $this->service->findById($serviceId);

        abort_if(!$service, Response::HTTP_NOT_FOUND);

        return new ServiceDetailResource($service);
    }
}
