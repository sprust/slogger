<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Exceptions\McpTraceDataFilterInvalidException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceGroupByInvalidException;
use App\Modules\Mcp\Domain\Services\McpTraceDataFilterParser;
use App\Modules\Mcp\Transports\McpTracePeriodTransport;
use App\Modules\Mcp\Parameters\FindMcpTraceGroupsParameters;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceGroupsAction;
use App\Modules\Mcp\Entities\Trace\Groups\TraceGroupsObject;
use App\Modules\Trace\Enums\TraceGroupFieldEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Parameters\TraceFindGroupsParameters;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Modules\Mcp\Transports\TraceObjectTransport;
use App\Modules\Mcp\Transports\TraceParametersTransport;

readonly class FindMcpTraceGroupsAction
{
    public const int LIMIT      = 100;

    public const int MAX_FIELDS = 3;

    public function __construct(
        private FindTraceGroupsAction $findTraceGroupsAction,
        private McpTraceDataFilterParser $dataFilterParser,
        private McpTracePeriodTransport $mcpTracePeriodTransport,
        private TraceObjectTransport $traceObjectTransport,
        private TraceParametersTransport $traceParametersTransport
    ) {
    }

    /**
     * @throws McpTraceGroupByInvalidException
     * @throws McpTraceDataFilterInvalidException
     * @throws ClickhouseQueryException
     */
    public function handle(FindMcpTraceGroupsParameters $parameters): TraceGroupsObject
    {
        $groupBy = $this->parseGroupBy($parameters->groupBy);
        $filter  = $this->traceParametersTransport->toTraceDataFilters(
            $this->dataFilterParser->parse($parameters->dataFilter)
        );
        $result  = $this->findTraceGroupsAction->handle(
            new TraceFindGroupsParameters(
                loggingPeriod: $this->mcpTracePeriodTransport->toLoggingPeriod($parameters->period),
                groupBy: $groupBy,
                limit: self::LIMIT,
                serviceIds: $parameters->serviceIds,
                types: $parameters->types,
                tags: $parameters->tags,
                statuses: $parameters->statuses,
                durationFrom: $parameters->durationFrom,
                durationTo: $parameters->durationTo,
                data: count($filter) > 0
                    ? new TraceDataFilterParameters(
                        filter: $filter,
                    )
                    : null,
            )
        );

        return $this->traceObjectTransport->fromTraceGroupsObject($result);
    }

    /**
     * @param string[] $values
     *
     * @return TraceGroupFieldEnum[]
     *
     * @throws McpTraceGroupByInvalidException
     */
    private function parseGroupBy(array $values): array
    {
        if (count($values) === 0 || count($values) > self::MAX_FIELDS) {
            throw new McpTraceGroupByInvalidException(
                message: sprintf('Group by 1 to %d fields.', self::MAX_FIELDS),
            );
        }

        if (count(array_unique($values)) !== count($values)) {
            throw new McpTraceGroupByInvalidException(
                message: 'A field is repeated.',
            );
        }

        $fields = [];

        foreach ($values as $value) {
            $field = TraceGroupFieldEnum::tryFrom($value);

            if (is_null($field)) {
                throw new McpTraceGroupByInvalidException(
                    message: "Unknown field [{$value}].",
                );
            }

            $fields[] = $field;
        }

        $timeFields = array_filter(
            $fields,
            static fn(TraceGroupFieldEnum $field) => $field === TraceGroupFieldEnum::Hour
                || $field === TraceGroupFieldEnum::Minute10,
        );

        if (count($timeFields) > 1) {
            throw new McpTraceGroupByInvalidException(
                message: 'Use hour or minute10, not both.',
            );
        }

        return $fields;
    }
}
