<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Services;

use App\Modules\Mcp\Entities\Bridges\McpTraceTreeNodeObject;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceServicesAction;
use App\Modules\Mcp\Parameters\TraceTreeNodeParameters;
use App\Modules\Mcp\Transports\TraceObjectTransport;

readonly class McpTraceTreeNodeFactory
{
    public function __construct(
        private FindTraceServicesAction $findTraceServicesAction,
        private TraceObjectTransport $traceObjectTransport
    ) {
    }

    /**
     * @param TraceTreeNodeParameters[] $nodes
     * @param array<string, int>|null   $childrenCounts
     *
     * @return McpTraceTreeNodeObject[]
     */
    public function make(array $nodes, ?array $childrenCounts): array
    {
        $serviceIds = array_values(
            array_unique(
                array_filter(
                    array_map(static fn(TraceTreeNodeParameters $node) => $node->serviceId, $nodes),
                    static fn(?int $serviceId) => !is_null($serviceId),
                ),
            )
        );
        $services = $this->findTraceServicesAction->handle(
            serviceIds: $serviceIds,
        );

        return array_map(
            function (
                TraceTreeNodeParameters $node
            ) use ($services, $childrenCounts): McpTraceTreeNodeObject {
                $service = $node->serviceId === null ? null : $services->getById($node->serviceId);

                return new McpTraceTreeNodeObject(
                    node: $this->traceObjectTransport->fromTraceTreeNodeParameters($node),
                    service: $service === null
                        ? null
                        : $this->traceObjectTransport->fromTraceServiceObject($service),
                    childrenCount: $childrenCounts === null
                        ? null
                        : $childrenCounts[$node->traceId] ?? 0,
                );
            },
            $nodes
        );
    }
}
