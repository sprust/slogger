<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Mcp\Entities\Bridges\McpServiceObject;
use App\Modules\Mcp\Transports\McpServiceObjectTransport;

readonly class FindMcpServicesAction
{
    public function __construct(
        private FindServicesAction $findServicesAction,
        private McpServiceObjectTransport $mcpServiceObjectTransport
    ) {
    }

    /**
     * @return McpServiceObject[]
     */
    public function handle(?string $query): array
    {
        $services = array_map(
            fn(ServiceObject $serviceObject): McpServiceObject => $this->mcpServiceObjectTransport->fromService(
                $serviceObject
            ),
            $this->findServicesAction->handle()
        );

        if (is_null($query) || $query === '') {
            return $services;
        }

        return array_values(
            array_filter(
                $services,
                static fn(McpServiceObject $service) => mb_stripos($service->name, $query) !== false,
            )
        );
    }
}
