<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Transports\McpTracePeriodTransport;
use App\Modules\Mcp\Transports\TraceObjectTransport;
use App\Modules\Mcp\Entities\Bridges\McpTraceFacetListObject;
use App\Modules\Mcp\Entities\Bridges\McpTraceFacetsObject;
use App\Modules\Mcp\Parameters\FindMcpTraceFacetsParameters;
use App\Modules\Trace\Domain\Actions\Queries\FindStatusesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTagsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTypesAction;
use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;
use App\Modules\Trace\Parameters\TraceFindStatusesParameters;
use App\Modules\Trace\Parameters\TraceFindTagsParameters;
use App\Modules\Trace\Parameters\TraceFindTypesParameters;
use App\Services\Clickhouse\ClickhouseQueryException;

readonly class FindMcpTraceFacetsAction
{
    public function __construct(
        private FindTypesAction $findTypesAction,
        private FindStatusesAction $findStatusesAction,
        private FindTagsAction $findTagsAction,
        private McpTracePeriodTransport $mcpTracePeriodTransport,
        private TraceObjectTransport $traceObjectTransport
    ) {
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function handle(FindMcpTraceFacetsParameters $parameters): McpTraceFacetsObject
    {
        $serviceIds    = $parameters->serviceIds;
        $loggingPeriod = $this->mcpTracePeriodTransport->toLoggingPeriod($parameters->period);
        $types         = $this->findTypesAction->handle(
            new TraceFindTypesParameters(
                serviceIds: $serviceIds,
                loggingPeriod: $loggingPeriod,
                statuses: $parameters->statuses,
            )
        );
        $statuses = $this->findStatusesAction->handle(
            new TraceFindStatusesParameters(
                serviceIds: $serviceIds,
                loggingPeriod: $loggingPeriod,
                types: $parameters->types,
            )
        );
        $tags = $this->findTagsAction->handle(
            new TraceFindTagsParameters(
                serviceIds: $serviceIds,
                loggingPeriod: $loggingPeriod,
                types: $parameters->types,
                statuses: $parameters->statuses,
            )
        );

        return new McpTraceFacetsObject(
            types: $this->makeList(
                items: $types,
                limit: $parameters->limit,
            ),
            statuses: $this->makeList(
                items: $statuses,
                limit: $parameters->limit,
            ),
            tags: $this->makeList(
                items: $tags,
                limit: $parameters->limit,
            ),
        );
    }

    /**
     * @param TraceStringFieldObject[] $items
     */
    private function makeList(array $items, int $limit): McpTraceFacetListObject
    {
        usort(
            $items,
            static fn(TraceStringFieldObject $a, TraceStringFieldObject $b): int => [
                $b->count,
                $a->name,
            ] <=> [
                $a->count,
                $b->name,
            ],
        );

        return new McpTraceFacetListObject(
            items: array_map(
                fn(
                    TraceStringFieldObject $traceStringFieldObject
                ) => $this->traceObjectTransport->fromTraceStringFieldObject($traceStringFieldObject),
                array_slice($items, 0, $limit)
            ),
            truncated: count($items) > $limit,
        );
    }
}
