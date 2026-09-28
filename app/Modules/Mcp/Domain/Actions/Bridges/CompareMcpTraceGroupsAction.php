<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Exceptions\McpTraceGroupByInvalidException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceGroupsOverlapException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexBuildingException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexFailedException;
use App\Modules\Mcp\Domain\Services\McpTraceIndexExceptionTranslator;
use App\Modules\Mcp\Domain\Services\McpTracePeriodMapper;
use App\Modules\Mcp\Parameters\CompareMcpTraceGroupsParameters;
use App\Modules\Trace\Domain\Actions\Queries\CompareTraceGroupsAction;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonObject;
use App\Modules\Trace\Enums\TraceCompareByEnum;
use App\Modules\Trace\Parameters\TraceCompareGroupsParameters;

readonly class CompareMcpTraceGroupsAction
{
    public const int LIMIT = 30;

    private const string DATA_PREFIX = 'data.';

    public function __construct(
        private CompareTraceGroupsAction $compareTraceGroupsAction,
        private McpTraceIndexExceptionTranslator $indexExceptionTranslator,
        private McpTracePeriodMapper $periodMapper
    ) {
    }

    /**
     * @throws McpTraceGroupByInvalidException
     * @throws McpTraceGroupsOverlapException
     * @throws McpTraceIndexBuildingException
     * @throws McpTraceIndexFailedException
     */
    public function handle(CompareMcpTraceGroupsParameters $parameters): TraceGroupComparisonObject
    {
        $overlap = array_values(array_intersect($parameters->groupBStatuses, $parameters->groupAStatuses));

        if (count($overlap) > 0) {
            throw new McpTraceGroupsOverlapException($overlap);
        }

        $dataKey = null;

        if (str_starts_with($parameters->by, self::DATA_PREFIX)) {
            $dataKey = substr($parameters->by, strlen(self::DATA_PREFIX));

            if (preg_match('/^[^\s"$]+$/', $dataKey) !== 1) {
                throw new McpTraceGroupByInvalidException("Invalid data key in [$parameters->by].");
            }

            $by = TraceCompareByEnum::Data;
        } else {
            $by = TraceCompareByEnum::tryFrom($parameters->by);

            if (is_null($by) || $by === TraceCompareByEnum::Data) {
                throw new McpTraceGroupByInvalidException("Unknown field [$parameters->by].");
            }
        }

        return $this->indexExceptionTranslator->call(
            fn() => $this->compareTraceGroupsAction->handle(
                new TraceCompareGroupsParameters(
                    loggingPeriod: $this->periodMapper->toLoggingPeriod($parameters->period),
                    groupAStatuses: $parameters->groupAStatuses,
                    groupBStatuses: $parameters->groupBStatuses,
                    by: $by,
                    dataKey: $dataKey,
                    limit: self::LIMIT,
                    serviceIds: $parameters->serviceIds,
                    types: $parameters->types
                )
            )
        );
    }
}
