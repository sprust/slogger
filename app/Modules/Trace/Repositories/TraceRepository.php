<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Modules\Trace\Entities\Trace\DeletedTracesObject;
use App\Modules\Trace\Entities\Trace\TraceDataRangeObject;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Repositories\Dto\Trace\Profiling\TraceProfilingDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;
use App\Modules\Trace\Repositories\Dto\Trace\Tree\TraceTreeNodeDto;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceFilterBuilder;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceRowReader;
use App\Modules\Trace\Repositories\Services\TraceDataToObjectBuilder;
use App\Services\Clickhouse\ClickhouseClient;
use App\Services\Clickhouse\ClickhouseQueryException;
use Illuminate\Support\Carbon;
use RuntimeException;

readonly class TraceRepository
{
    private const string TRACE_COLUMNS = 'sid, tid, ptid, tp, st, tgs, dt_raw, dur, mem, cpu, lat, cat, uat';

    private const string NODE_COLUMNS = 'sid, tid, ptid, tp, st, tgs, dur, mem, cpu, lat';

    public function __construct(
        private ClickhouseClient $client,
        private ClickhouseTraceFilterBuilder $filterBuilder,
        private ClickhouseTraceRowReader $rowReader,
    ) {
    }

    /**
     * The latest version of the trace, read without FINAL: the one row the id points to
     * is sorted out here instead of merging every part it may be in.
     *
     * @throws ClickhouseQueryException
     */
    public function findOneDetailByTraceId(string $traceId): ?TraceDto
    {
        $rows = $this->client->select(
            sql: 'SELECT ' . self::TRACE_COLUMNS . ' FROM traces WHERE tid = {tid:String} ORDER BY uat DESC LIMIT 1',
            params: ['tid' => $traceId],
            queryIdPrefix: 'trace-detail'
        );

        return isset($rows[0]) ? $this->makeTraceDto($rows[0]) : null;
    }

    /**
     * @param int[]|null    $serviceIds
     * @param string[]|null $traceIds
     * @param string[]      $types
     * @param string[]      $tags
     * @param string[]      $statuses
     *
     * @return TraceDto[]
     *
     * @throws ClickhouseQueryException
     */
    public function find(
        int $page = 1,
        int $perPage = 20,
        ?array $serviceIds = null,
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $tags = [],
        array $statuses = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterParameters $data = null,
        ?bool $hasProfiling = null,
    ): array {
        $condition = $this->filterBuilder->build(
            serviceIds: $serviceIds,
            traceIds: $traceIds,
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            types: $types,
            tags: $tags,
            statuses: $statuses,
            durationFrom: $durationFrom,
            durationTo: $durationTo,
            memoryFrom: $memoryFrom,
            memoryTo: $memoryTo,
            cpuFrom: $cpuFrom,
            cpuTo: $cpuTo,
            data: $data,
            hasProfiling: $hasProfiling,
        );

        $rows = $this->client->select(
            sql: sprintf(
                'SELECT %s FROM traces FINAL WHERE %s ORDER BY lat DESC, tid LIMIT {limit:UInt32} OFFSET {offset:UInt32}',
                self::TRACE_COLUMNS,
                $condition->sql
            ),
            params: [
                ...$condition->params,
                'limit'  => $perPage,
                'offset' => max(0, $page - 1) * $perPage,
            ],
            queryIdPrefix: 'trace-list'
        );

        return array_map(
            fn(array $row): TraceDto => $this->makeTraceDto($row),
            $rows
        );
    }

    /**
     * The traces of a tree level, read as nodes of it: the ten fields a tree node shows,
     * without the data, which is the one part of a trace that has no ceiling.
     *
     * @param string[] $traceIds
     *
     * @return TraceTreeNodeDto[]
     *
     * @throws ClickhouseQueryException
     */
    public function findTreeNodesByTraceIds(array $traceIds): array
    {
        if ($traceIds === []) {
            return [];
        }

        $rows = $this->client->select(
            sql: sprintf(
                'SELECT %s FROM traces WHERE tid IN {tids:Array(String)} ORDER BY uat DESC LIMIT 1 BY sid, tid',
                self::NODE_COLUMNS
            ),
            params: ['tids' => array_values($traceIds)],
            queryIdPrefix: 'trace-tree-nodes'
        );

        return array_map(
            fn(array $row): TraceTreeNodeDto => new TraceTreeNodeDto(
                serviceId: $this->rowReader->serviceId($row),
                traceId: (string) $row['tid'],
                parentTraceId: $this->rowReader->parentTraceId($row),
                type: (string) $row['tp'],
                status: (string) $row['st'],
                tags: $this->rowReader->tags($row),
                duration: $this->rowReader->float($row['dur'] ?? null),
                memory: $this->rowReader->float($row['mem'] ?? null),
                cpu: $this->rowReader->float($row['cpu'] ?? null),
                loggedAt: $this->rowReader->time($row['lat']),
            ),
            $rows
        );
    }

    /**
     * The first and the last hour traces are stored for, both null when there are none.
     *
     * Read from the partitions, which are hours, rather than from the rows.
     *
     * @throws ClickhouseQueryException
     */
    public function findHourRange(): TraceDataRangeObject
    {
        $rows = $this->client->select(
            sql: "SELECT min(hour) AS first, max(hour) AS last FROM (SELECT DISTINCT "
            . "toDateTime64(parseDateTime64BestEffort(partition, 0, 'UTC'), 6, 'UTC') AS hour "
            . "FROM system.parts WHERE database = currentDatabase() AND table = 'traces' AND active AND rows > 0) "
            . 'HAVING count() > 0',
            queryIdPrefix: 'trace-range'
        );

        if (!isset($rows[0])) {
            return new TraceDataRangeObject(firstHour: null, lastHour: null);
        }

        return new TraceDataRangeObject(
            firstHour: $this->rowReader->time($rows[0]['first']),
            lastHour: $this->rowReader->time($rows[0]['last'])
        );
    }

    /**
     * Profiling is not stored: the receiver has never written it.
     */
    public function findProfilingByTraceId(string $traceId): ?TraceProfilingDto
    {
        return null;
    }

    /**
     * Drops the hourly partitions that ended before the moment given.
     *
     * @throws ClickhouseQueryException
     */
    public function deletePartitions(Carbon $loggedAtTo): DeletedTracesObject
    {
        $partitions = $this->client->select(
            sql: 'SELECT partition_id, sum(rows) AS rows FROM system.parts '
            . "WHERE database = currentDatabase() AND table = 'traces' AND active "
            . 'AND parseDateTime64BestEffort(partition, 0, \'UTC\') + INTERVAL 1 HOUR <= {to:DateTime64(6, \'UTC\')} '
            . 'GROUP BY partition_id ORDER BY partition_id',
            params: ['to' => $loggedAtTo],
            queryIdPrefix: 'trace-clear'
        );

        $partitionsCount = 0;
        $tracesCount     = 0;

        foreach ($partitions as $partition) {
            $partitionId = (string) $partition['partition_id'];

            // An identifier of the statement, which takes no parameters: ClickHouse makes
            // these ids of hex digits, and nothing else is let through.
            if (!preg_match('/^[0-9a-f]+$/', $partitionId)) {
                throw new RuntimeException(sprintf('Unexpected partition id [%s]', $partitionId));
            }

            $this->client->command(
                sql: "ALTER TABLE traces DROP PARTITION ID '$partitionId'",
                queryIdPrefix: 'trace-clear'
            );

            ++$partitionsCount;
            $tracesCount += (int) $partition['rows'];
        }

        return new DeletedTracesObject(
            partitionsCount: $partitionsCount,
            tracesCount: $tracesCount,
        );
    }

    /**
     * Merges each hourly partition that ended before the moment given and is still in more
     * than one part into a single part, with one row per trace.
     *
     * A trace written before it was complete — a create, then its update — has two rows
     * until their parts merge, and the background merges promise no moment for that. A
     * partition in one part holds no such pair, and FINAL has nothing to merge in it.
     * Partitions already in one part are left alone, so the next run costs nothing unless
     * late traces have reached an old hour since.
     *
     * @return int the number of partitions merged
     *
     * @throws ClickhouseQueryException
     */
    public function optimizePartitions(Carbon $loggedAtTo): int
    {
        $partitions = $this->client->select(
            sql: 'SELECT partition_id FROM system.parts '
            . "WHERE database = currentDatabase() AND table = 'traces' AND active "
            . 'AND parseDateTime64BestEffort(partition, 0, \'UTC\') + INTERVAL 1 HOUR <= {to:DateTime64(6, \'UTC\')} '
            . 'GROUP BY partition_id HAVING count() > 1 ORDER BY partition_id',
            params: ['to' => $loggedAtTo],
            queryIdPrefix: 'trace-optimize'
        );

        foreach ($partitions as $partition) {
            $partitionId = (string) $partition['partition_id'];

            // An identifier of the statement, which takes no parameters: ClickHouse makes
            // these ids of hex digits, and nothing else is let through.
            if (!preg_match('/^[0-9a-f]+$/', $partitionId)) {
                throw new RuntimeException(sprintf('Unexpected partition id [%s]', $partitionId));
            }

            $this->client->command(
                sql: "OPTIMIZE TABLE traces PARTITION ID '$partitionId' FINAL",
                queryIdPrefix: 'trace-optimize'
            );
        }

        return count($partitions);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function makeTraceDto(array $row): TraceDto
    {
        return new TraceDto(
            id: (string) $row['tid'],
            serviceId: $this->rowReader->serviceId($row),
            traceId: (string) $row['tid'],
            parentTraceId: $this->rowReader->parentTraceId($row),
            type: (string) $row['tp'],
            status: (string) $row['st'],
            tags: $this->rowReader->tags($row),
            data: new TraceDataToObjectBuilder(
                $this->rowReader->data((string) $row['dt_raw'])
            )->build(),
            duration: $this->rowReader->float($row['dur'] ?? null),
            memory: $this->rowReader->float($row['mem'] ?? null),
            cpu: $this->rowReader->float($row['cpu'] ?? null),
            hasProfiling: false,
            loggedAt: $this->rowReader->time($row['lat']),
            createdAt: $this->rowReader->time($row['cat']),
            updatedAt: $this->rowReader->time($row['uat'])
        );
    }
}
