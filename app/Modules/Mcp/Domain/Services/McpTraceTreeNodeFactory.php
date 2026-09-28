<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Services;

use App\Modules\Mcp\Entities\Bridges\McpTraceTreeNodeObject;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceServicesAction;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;

readonly class McpTraceTreeNodeFactory
{
    public function __construct(
        private FindTraceServicesAction $findTraceServicesAction
    ) {
    }

    /**
     * @param TraceTreeRawObject[]    $nodes
     * @param array<string, int>|null $childrenCounts
     *
     * @return McpTraceTreeNodeObject[]
     */
    public function make(array $nodes, ?array $childrenCounts): array
    {
        $serviceIds = array_values(
            array_unique(
                array_filter(
                    array_map(static fn(TraceTreeRawObject $node) => $node->serviceId, $nodes),
                    static fn(?int $serviceId) => !is_null($serviceId)
                )
            )
        );

        $services = $this->findTraceServicesAction->handle($serviceIds);

        return array_map(
            static fn(TraceTreeRawObject $node) => new McpTraceTreeNodeObject(
                node: $node,
                service: is_null($node->serviceId) ? null : $services->getById($node->serviceId),
                childrenCount: is_null($childrenCounts) ? null : ($childrenCounts[$node->traceId] ?? 0)
            ),
            $nodes
        );
    }
}
