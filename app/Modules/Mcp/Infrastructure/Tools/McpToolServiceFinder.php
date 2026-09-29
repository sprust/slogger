<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpServicesAction;
use App\Modules\Service\Entities\ServiceObject;

readonly class McpToolServiceFinder
{
    public function __construct(
        private FindMcpServicesAction $findMcpServicesAction
    ) {
    }

    public function names(): McpToolServiceNames
    {
        return new McpToolServiceNames($this->findMcpServicesAction->handle(null));
    }

    /**
     * @param int[] $serviceIds
     *
     * @return int[]
     */
    public function findUnknownIds(array $serviceIds): array
    {
        if (count($serviceIds) === 0) {
            return [];
        }

        $knownIds = array_map(
            static fn(ServiceObject $service) => $service->id,
            $this->findMcpServicesAction->handle(null)
        );

        return array_values(array_diff($serviceIds, $knownIds));
    }
}
