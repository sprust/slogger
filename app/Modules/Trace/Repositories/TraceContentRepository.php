<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Repositories\Dto\Trace\TraceSqlConditionDto;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceFilterBuilder;
use App\Services\Clickhouse\ClickhouseClient;
use App\Services\Clickhouse\ClickhouseQueryException;
use Illuminate\Support\Carbon;

/**
 * The values of the trace filters with how many traces carry each: types, tags,
 * statuses. The most frequent first, fifty at most.
 */
readonly class TraceContentRepository
{
    private const int LIMIT = 50;

    public function __construct(
        private ClickhouseClient $client,
        private ClickhouseTraceFilterBuilder $filterBuilder,
    ) {
    }

    /**
     * @param int[]    $serviceIds
     * @param string[] $tags
     * @param string[] $statuses
     *
     * @return TraceStringFieldObject[]
     *
     * @throws ClickhouseQueryException
     */
    public function findTypes(
        array $serviceIds = [],
        ?string $text = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $tags = [],
        array $statuses = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterParameters $data = null,
    ): array {
        return $this->findValues(
            value: 'tp',
            text: $text,
            condition: $this->filterBuilder->build(
                serviceIds: $serviceIds,
                loggedAtFrom: $loggedAtFrom,
                loggedAtTo: $loggedAtTo,
                tags: $tags,
                statuses: $statuses,
                durationFrom: $durationFrom,
                durationTo: $durationTo,
                memoryFrom: $memoryFrom,
                memoryTo: $memoryTo,
                cpuFrom: $cpuFrom,
                cpuTo: $cpuTo,
                data: $data,
            ),
            queryIdPrefix: 'trace-types'
        );
    }

    /**
     * @param int[]    $serviceIds
     * @param string[] $types
     * @param string[] $statuses
     *
     * @return TraceStringFieldObject[]
     *
     * @throws ClickhouseQueryException
     */
    public function findTags(
        array $serviceIds = [],
        ?string $text = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $statuses = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterParameters $data = null,
    ): array {
        return $this->findValues(
            value: 'arrayJoin(tgs)',
            text: $text,
            condition: $this->filterBuilder->build(
                serviceIds: $serviceIds,
                loggedAtFrom: $loggedAtFrom,
                loggedAtTo: $loggedAtTo,
                types: $types,
                statuses: $statuses,
                durationFrom: $durationFrom,
                durationTo: $durationTo,
                memoryFrom: $memoryFrom,
                memoryTo: $memoryTo,
                cpuFrom: $cpuFrom,
                cpuTo: $cpuTo,
                data: $data,
            ),
            queryIdPrefix: 'trace-tags'
        );
    }

    /**
     * @param int[]    $serviceIds
     * @param string[] $types
     * @param string[] $tags
     *
     * @return TraceStringFieldObject[]
     *
     * @throws ClickhouseQueryException
     */
    public function findStatuses(
        array $serviceIds = [],
        ?string $text = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $tags = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterParameters $data = null,
    ): array {
        return $this->findValues(
            value: 'st',
            text: $text,
            condition: $this->filterBuilder->build(
                serviceIds: $serviceIds,
                loggedAtFrom: $loggedAtFrom,
                loggedAtTo: $loggedAtTo,
                types: $types,
                tags: $tags,
                durationFrom: $durationFrom,
                durationTo: $durationTo,
                memoryFrom: $memoryFrom,
                memoryTo: $memoryTo,
                cpuFrom: $cpuFrom,
                cpuTo: $cpuTo,
                data: $data,
            ),
            queryIdPrefix: 'trace-statuses'
        );
    }

    /**
     * @return TraceStringFieldObject[]
     *
     * @throws ClickhouseQueryException
     */
    private function findValues(
        string $value,
        ?string $text,
        TraceSqlConditionDto $condition,
        string $queryIdPrefix
    ): array {
        $params = [
            ...$condition->params,
            'limit' => self::LIMIT,
        ];

        // on the value itself, after the tags are unrolled: a trace is counted under the
        // tags that match, not under all of its tags
        $having = '';

        if ($text) {
            $having = 'HAVING position(name, {text:String}) > 0';

            $params['text'] = $text;
        }

        $rows = $this->client->select(
            sql: sprintf(
                'SELECT %s AS name, count() AS count FROM traces FINAL WHERE %s GROUP BY name %s '
                . 'ORDER BY count DESC, name LIMIT {limit:UInt32}',
                $value,
                $condition->sql,
                $having
            ),
            params: $params,
            queryIdPrefix: $queryIdPrefix
        );

        return array_map(
            static fn(array $row): TraceStringFieldObject => new TraceStringFieldObject(
                name: (string) $row['name'],
                count: (int) $row['count']
            ),
            $rows
        );
    }
}
