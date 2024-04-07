<?php

namespace App\Modules\TraceCollector\Adapters\Service;

use App\Modules\Service\Api\ServiceApi;
use App\Modules\Service\Http\Middlewares\AuthServiceMiddleware;
use App\Modules\TraceCollector\Domain\Entities\Objects\ServiceObject;

readonly class ServiceAdapter
{
    public function __construct(private ServiceApi $serviceApi)
    {
    }

    public function getAuthMiddleware(): string
    {
        return AuthServiceMiddleware::class;
    }

    public function getService(): ?ServiceObject
    {
        $service = $this->serviceApi->getCurrentService();

        if (!$service) {
            return null;
        }

        return new ServiceObject(
            id: $service->id,
            name: $service->name
        );
    }
}
