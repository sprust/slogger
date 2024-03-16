<?php

namespace App\Modules\TraceCollector\Adapters\Service;

use App\Modules\Service\Api\ServiceApi;
use App\Modules\Service\Http\Middlewares\AuthServiceMiddleware;
use App\Modules\TraceCollector\Adapters\Service\Dto\ServiceDto;

readonly class ServiceAdapter
{
    public function __construct(private ServiceApi $serviceApi)
    {
    }

    public function getAuthMiddleware(): string
    {
        return AuthServiceMiddleware::class;
    }

    public function getService(): ?ServiceDto
    {
        $service = $this->serviceApi->getCurrentService();

        if (!$service) {
            return null;
        }

        return new ServiceDto(
            id: $service->id,
            name: $service->name
        );
    }
}
