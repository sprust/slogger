<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Entities\ServiceObject;

readonly class FindMcpServicesAction
{
    public function __construct(
        private FindServicesAction $findServicesAction
    ) {
    }

    /**
     * @return ServiceObject[]
     */
    public function handle(?string $query): array
    {
        $services = $this->findServicesAction->handle();

        if (is_null($query) || $query === '') {
            return $services;
        }

        return array_values(
            array_filter(
                $services,
                static fn(ServiceObject $service) => mb_stripos($service->name, $query) !== false
            )
        );
    }
}
