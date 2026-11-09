<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Exceptions\McpTraceGroupByInvalidException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceGroupsOverlapException;
use App\Modules\Mcp\Transports\McpTracePeriodTransport;
use App\Modules\Mcp\Parameters\CompareMcpTraceGroupsParameters;
use App\Modules\Trace\Domain\Actions\Queries\CompareTraceGroupsAction;
use App\Modules\Mcp\Entities\Trace\Groups\TraceGroupComparisonObject;
use App\Modules\Trace\Enums\TraceCompareByEnum;
use App\Modules\Trace\Parameters\TraceCompareGroupsParameters;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Modules\Mcp\Transports\TraceObjectTransport;

readonly class CompareMcpTraceGroupsAction
{
    public const int LIMIT           = 30;

    private const string DATA_PREFIX = 'data.';

    public function __construct(private CompareTraceGroupsAction $compareTraceGroupsAction, private McpTracePeriodTransport $mcpTracePeriodTransport, private TraceObjectTransport $traceObjectTransport)
    {
    }

    /**
     * @throws McpTraceGroupByInvalidException
     * @throws McpTraceGroupsOverlapException
     * @throws ClickhouseQueryException
     */
    public function handle(CompareMcpTraceGroupsParameters $parameters): TraceGroupComparisonObject
    {
        $overlap = array_values(array_intersect($parameters->groupBStatuses, $parameters->groupAStatuses));

        if (count($overlap) > 0) {
            throw new McpTraceGroupsOverlapException($overlap);
        }

        $dataKey = null;

        if (
            str_starts_with(
                $parameters->by,
                self::DATA_PREFIX,
            )
        ) {
            $dataKey = substr(
                $parameters->by,
                strlen(self::DATA_PREFIX),
            );

            // the key is written into the query: names joined by dots and nothing else
            if (
                preg_match(
                    '/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)*\z/',
                    $dataKey,
                ) !== 1
            ) {
                throw new McpTraceGroupByInvalidException(
                    message: "Invalid data key in [{$parameters->by}].",
                );
            }

            $by = TraceCompareByEnum::Data;
        } else {
            $by = TraceCompareByEnum::tryFrom($parameters->by);

            if (is_null($by) || $by === TraceCompareByEnum::Data) {
                throw new McpTraceGroupByInvalidException(
                    message: "Unknown field [{$parameters->by}].",
                );
            }
        }

        $result = $this->compareTraceGroupsAction->handle(
            new TraceCompareGroupsParameters(
                loggingPeriod: $this->mcpTracePeriodTransport->toLoggingPeriod($parameters->period),
                groupAStatuses: $parameters->groupAStatuses,
                groupBStatuses: $parameters->groupBStatuses,
                by: $by,
                dataKey: $dataKey,
                limit: self::LIMIT,
                serviceIds: $parameters->serviceIds,
                types: $parameters->types,
            )
        );

        return $this->traceObjectTransport->fromTraceGroupComparisonObject($result);
    }
}
