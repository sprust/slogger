<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonCountObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupObject;
use App\Modules\Trace\Enums\TraceCompareByEnum;
use App\Modules\Trace\Enums\TraceGroupFieldEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceFilterBuilder;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceRowReader;
use App\Modules\Trace\Repositories\Services\TraceDataPathResolver;
use App\Services\Clickhouse\ClickhouseClient;
use App\Services\Clickhouse\ClickhouseQueryException;
use Illuminate\Support\Carbon;

readonly class TraceGroupsRepository
{
    public function __construct(
        private ClickhouseClient $client,
        private ClickhouseTraceFilterBuilder $filterBuilder,
        private ClickhouseTraceRowReader $rowReader,
        private TraceDataPathResolver $pathResolver,
    ) {
    }

    /**
     * @param TraceGroupFieldEnum[] $groupBy
     * @param int[]                 $serviceIds
     * @param string[]              $types
     * @param string[]              $tags
     * @param string[]              $statuses
     *
     * @return TraceGroupObject[]
     *
     * @throws ClickhouseQueryException
     */
    public function findGroups(
        Carbon $loggedAtFrom,
        Carbon $loggedAtTo,
        array $groupBy,
        int $limit,
        array $serviceIds = [],
        array $types = [],
        array $tags = [],
        array $statuses = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?TraceDataFilterParameters $data = null,
    ): array {
        $condition = $this->filterBuilder->build(
            serviceIds: $serviceIds,
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            types: $types,
            tags: $tags,
            statuses: $statuses,
            durationFrom: $durationFrom,
            durationTo: $durationTo,
            data: $data,
        );

        $keys    = [];
        $orderBy = [];

        $timeField = null;

        foreach ($groupBy as $field) {
            $keys[] = sprintf(
                '%s AS %s',
                match ($field) {
                    TraceGroupFieldEnum::Service  => 'sid',
                    TraceGroupFieldEnum::Type     => 'tp',
                    TraceGroupFieldEnum::Status   => 'st',
                    TraceGroupFieldEnum::Hour     => "toDateTime64(toStartOfHour(lat), 6, 'UTC')",
                    TraceGroupFieldEnum::Minute10 => "toDateTime64(toStartOfTenMinutes(lat), 6, 'UTC')",
                },
                $field->value
            );

            if ($field === TraceGroupFieldEnum::Hour || $field === TraceGroupFieldEnum::Minute10) {
                $timeField = $field;
            }
        }

        if (!is_null($timeField)) {
            $orderBy[] = $timeField->value;
        }

        $orderBy[] = 'count DESC';

        foreach ($groupBy as $field) {
            if ($field !== $timeField) {
                $orderBy[] = $field->value;
            }
        }

        $rows = $this->client->select(
            sql: sprintf(
                'SELECT %s, count() AS count, avg(dur) AS avg, quantile(0.95)(dur) AS p95, max(dur) AS max, '
                . 'argMax(tid, coalesce(dur, -1)) AS example FROM traces FINAL WHERE %s '
                . 'GROUP BY %s ORDER BY %s LIMIT {limit:UInt32}',
                implode(', ', $keys),
                $condition->sql,
                implode(
                    ', ',
                    array_map(static fn(TraceGroupFieldEnum $field) => $field->value, $groupBy)
                ),
                implode(', ', $orderBy)
            ),
            params: [
                ...$condition->params,
                'limit' => $limit,
            ],
            queryIdPrefix: 'trace-groups'
        );

        return array_map(
            function (array $row) use ($timeField): TraceGroupObject {
                return new TraceGroupObject(
                    serviceId: is_numeric($row['service'] ?? null) ? (int) $row['service'] : null,
                    type: is_string($row['type'] ?? null) ? $row['type'] : null,
                    status: is_string($row['status'] ?? null) ? $row['status'] : null,
                    startedAt: is_null($timeField) ? null : $this->rowReader->time($row[$timeField->value]),
                    count: (int) $row['count'],
                    durationAvg: $this->readFloat($row['avg'] ?? null),
                    durationP95: $this->readFloat($row['p95'] ?? null),
                    durationMax: $this->readFloat($row['max'] ?? null),
                    exampleTraceId: ($row['example'] ?? '') === '' ? null : (string) $row['example']
                );
            },
            $rows
        );
    }

    /**
     * How the traces of the period split by the value of one field, in group A (the
     * statuses given) and outside it.
     *
     * @param string[] $groupAStatuses
     * @param string[] $statuses
     * @param int[]    $serviceIds
     * @param string[] $types
     *
     * @return TraceGroupComparisonCountObject[]
     *
     * @throws ClickhouseQueryException
     */
    public function compareGroups(
        Carbon $loggedAtFrom,
        Carbon $loggedAtTo,
        array $groupAStatuses,
        TraceCompareByEnum $by,
        ?string $dataKey,
        array $serviceIds = [],
        array $types = [],
        array $statuses = []
    ): array {
        $condition = $this->filterBuilder->build(
            serviceIds: $serviceIds,
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            types: $types,
            statuses: $statuses,
        );

        $value = match ($by) {
            TraceCompareByEnum::Type    => 'tp',
            TraceCompareByEnum::Service => 'sid',
            // a trace without tags is one row under no value
            TraceCompareByEnum::Tag     => "nullIf(arrayJoin(if(empty(tgs), [''], tgs)), '')",
            // the value as stored, its type included: 500 and "500" are two values
            TraceCompareByEnum::Data    => $this->pathResolver->resolve((string) $dataKey)->objectExpression,
        };

        $rows = $this->client->select(
            sql: sprintf(
                'SELECT st IN {groupA:Array(String)} AS inA, %s AS value, count() AS count '
                . 'FROM traces FINAL WHERE %s GROUP BY inA, value',
                $value,
                $condition->sql
            ),
            params: [
                ...$condition->params,
                'groupA' => array_values($groupAStatuses),
            ],
            queryIdPrefix: 'trace-compare',
            settings: ['allow_suspicious_types_in_group_by' => 1]
        );

        return array_map(
            fn(array $row): TraceGroupComparisonCountObject => new TraceGroupComparisonCountObject(
                value: $this->readValue($row['value'] ?? null),
                inGroupA: (bool) $row['inA'],
                count: (int) $row['count']
            ),
            $rows
        );
    }

    private function readFloat(mixed $value): ?float
    {
        return is_numeric($value) ? round((float) $value, 6) : null;
    }

    private function readValue(mixed $value): string|int|float|bool|null
    {
        if (is_null($value) || is_scalar($value)) {
            return $value;
        }

        return (string) json_encode($value);
    }
}
