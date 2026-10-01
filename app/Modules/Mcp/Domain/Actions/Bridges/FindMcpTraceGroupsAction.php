<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Exceptions\McpTraceDataFilterInvalidException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceGroupByInvalidException;
use App\Modules\Mcp\Domain\Services\McpTraceDataFilterParser;
use App\Modules\Mcp\Domain\Services\McpTracePeriodMapper;
use App\Modules\Mcp\Parameters\FindMcpTraceGroupsParameters;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceGroupsAction;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupsObject;
use App\Modules\Trace\Enums\TraceGroupFieldEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Parameters\TraceFindGroupsParameters;
use App\Services\Clickhouse\ClickhouseQueryException;

readonly class FindMcpTraceGroupsAction
{
    public const int LIMIT      = 100;
    public const int MAX_FIELDS = 3;

    public function __construct(
        private FindTraceGroupsAction $findTraceGroupsAction,
        private McpTraceDataFilterParser $dataFilterParser,
        private McpTracePeriodMapper $periodMapper
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

        $filter = $this->dataFilterParser->parse($parameters->dataFilter);

        return $this->findTraceGroupsAction->handle(
            new TraceFindGroupsParameters(
                loggingPeriod: $this->periodMapper->toLoggingPeriod($parameters->period),
                groupBy: $groupBy,
                limit: self::LIMIT,
                serviceIds: $parameters->serviceIds,
                types: $parameters->types,
                tags: $parameters->tags,
                statuses: $parameters->statuses,
                durationFrom: $parameters->durationFrom,
                durationTo: $parameters->durationTo,
                data: count($filter) > 0 ? new TraceDataFilterParameters(filter: $filter) : null
            )
        );
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
                sprintf('Group by 1 to %d fields.', self::MAX_FIELDS)
            );
        }

        if (count(array_unique($values)) !== count($values)) {
            throw new McpTraceGroupByInvalidException('A field is repeated.');
        }

        $fields = [];

        foreach ($values as $value) {
            $field = TraceGroupFieldEnum::tryFrom($value);

            if (is_null($field)) {
                throw new McpTraceGroupByInvalidException("Unknown field [$value].");
            }

            $fields[] = $field;
        }

        $timeFields = array_filter(
            $fields,
            static fn(TraceGroupFieldEnum $field) => $field === TraceGroupFieldEnum::Hour
                || $field === TraceGroupFieldEnum::Minute10
        );

        if (count($timeFields) > 1) {
            throw new McpTraceGroupByInvalidException('Use hour or minute10, not both.');
        }

        return $fields;
    }
}
