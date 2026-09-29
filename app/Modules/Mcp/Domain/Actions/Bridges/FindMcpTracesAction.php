<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Exceptions\McpTraceDataFilterInvalidException;
use App\Modules\Mcp\Domain\Services\McpTraceDataFilterParser;
use App\Modules\Mcp\Domain\Services\McpTracePeriodMapper;
use App\Modules\Mcp\Parameters\FindMcpTracesParameters;
use App\Modules\Trace\Domain\Actions\Queries\FindTracesAction;
use App\Modules\Trace\Entities\Trace\TraceItemObjects;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Parameters\TraceFindParameters;

readonly class FindMcpTracesAction
{
    public const int PER_PAGE = 20;

    public function __construct(
        private FindTracesAction $findTracesAction,
        private McpTraceDataFilterParser $dataFilterParser,
        private McpTracePeriodMapper $periodMapper
    ) {
    }

    /**
     * @throws McpTraceDataFilterInvalidException
     */
    public function handle(FindMcpTracesParameters $parameters): TraceItemObjects
    {
        $filter = $this->dataFilterParser->parse($parameters->dataFilter);

        $data = (count($filter) > 0 || count($parameters->dataFields) > 0)
            ? new TraceDataFilterParameters(
                filter: $filter,
                fields: $parameters->dataFields
            )
            : null;

        return $this->findTracesAction->handle(
            new TraceFindParameters(
                page: $parameters->page,
                perPage: self::PER_PAGE,
                serviceIds: $parameters->serviceIds,
                loggingPeriod: $this->periodMapper->toLoggingPeriod($parameters->period),
                types: $parameters->types,
                tags: $parameters->tags,
                statuses: $parameters->statuses,
                durationFrom: $parameters->durationFrom,
                durationTo: $parameters->durationTo,
                data: $data
            )
        );
    }
}
