<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexBuildingException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexFailedException;
use App\Modules\Mcp\Domain\Services\McpTraceIndexExceptionTranslator;
use App\Modules\Mcp\Domain\Services\McpTracePeriodMapper;
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

readonly class FindMcpTraceFacetsAction
{
    public function __construct(
        private FindTypesAction $findTypesAction,
        private FindStatusesAction $findStatusesAction,
        private FindTagsAction $findTagsAction,
        private McpTraceIndexExceptionTranslator $indexExceptionTranslator,
        private McpTracePeriodMapper $periodMapper
    ) {
    }

    /**
     * @throws McpTraceIndexBuildingException
     * @throws McpTraceIndexFailedException
     */
    public function handle(FindMcpTraceFacetsParameters $parameters): McpTraceFacetsObject
    {
        $serviceIds    = $parameters->serviceIds;
        $loggingPeriod = $this->periodMapper->toLoggingPeriod($parameters->period);

        $types = $this->indexExceptionTranslator->call(
            fn() => $this->findTypesAction->handle(
                new TraceFindTypesParameters(
                    serviceIds: $serviceIds,
                    loggingPeriod: $loggingPeriod,
                    statuses: $parameters->statuses
                )
            )
        );

        $statuses = $this->indexExceptionTranslator->call(
            fn() => $this->findStatusesAction->handle(
                new TraceFindStatusesParameters(
                    serviceIds: $serviceIds,
                    loggingPeriod: $loggingPeriod,
                    types: $parameters->types
                )
            )
        );

        $tags = $this->indexExceptionTranslator->call(
            fn() => $this->findTagsAction->handle(
                new TraceFindTagsParameters(
                    serviceIds: $serviceIds,
                    loggingPeriod: $loggingPeriod,
                    types: $parameters->types,
                    statuses: $parameters->statuses
                )
            )
        );

        return new McpTraceFacetsObject(
            types: $this->makeList($types, $parameters->limit),
            statuses: $this->makeList($statuses, $parameters->limit),
            tags: $this->makeList($tags, $parameters->limit)
        );
    }

    /**
     * @param TraceStringFieldObject[] $items
     */
    private function makeList(array $items, int $limit): McpTraceFacetListObject
    {
        usort(
            $items,
            static fn(TraceStringFieldObject $a, TraceStringFieldObject $b): int => [$b->count, $a->name]
                <=> [$a->count, $b->name]
        );

        return new McpTraceFacetListObject(
            items: array_slice($items, 0, $limit),
            truncated: count($items) > $limit
        );
    }
}
