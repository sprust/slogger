<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Modules\Trace\Entities\Trace\TraceDataRangeObject;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Repositories\Dto\Trace\Partition\TracePartitionDto;
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

    // the ids travel in the URL of the query, which ClickHouse caps at 1 MiB
    private const int TRACE_IDS_PER_QUERY = 5000;

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
        $rows = [];

        foreach (array_chunk(array_values($traceIds), self::TRACE_IDS_PER_QUERY) as $traceIdsChunk) {
            $chunkRows = $this->client->select(
                sql: sprintf(
                    'SELECT %s FROM traces WHERE tid IN {tids:Array(String)} ORDER BY uat DESC LIMIT 1 BY sid, tid',
                    self::NODE_COLUMNS
                ),
                params: ['tids' => $traceIdsChunk],
                queryIdPrefix: 'trace-tree-nodes'
            );

            array_push($rows, ...$chunkRows);
        }

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
     * The hourly partitions whose hour ended no later than the moment given, oldest first.
     *
     * @return TracePartitionDto[]
     *
     * @throws ClickhouseQueryException
     */
    public function findEndedPartitions(Carbon $loggedAtTo): array
    {
        $partitions = $this->client->select(
            sql: 'SELECT partition_id, sum(rows) AS rows FROM system.parts '
            . "WHERE database = currentDatabase() AND table = 'traces' AND active "
            . 'AND parseDateTime64BestEffort(partition, 0, \'UTC\') + INTERVAL 1 HOUR <= {to:DateTime64(6, \'UTC\')} '
            . 'GROUP BY partition_id ORDER BY partition_id',
            params: ['to' => $loggedAtTo],
            queryIdPrefix: 'trace-clear'
        );

        return array_map(
            fn(array $partition): TracePartitionDto => new TracePartitionDto(
                id: $this->checkPartitionId((string) $partition['partition_id']),
                rowsCount: (int) $partition['rows'],
            ),
            $partitions
        );
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function dropPartition(string $partitionId): void
    {
        $this->client->command(
            sql: sprintf("ALTER TABLE traces DROP PARTITION ID '%s'", $this->checkPartitionId($partitionId)),
            queryIdPrefix: 'trace-clear'
        );
    }

    /**
     * A partition id is an identifier of the statement, which takes no parameters:
     * ClickHouse makes these ids of hex digits, and nothing else is let through.
     */
    private function checkPartitionId(string $partitionId): string
    {
        if (!preg_match('/^[0-9a-f]+\z/', $partitionId)) {
            throw new RuntimeException(sprintf('Unexpected partition id [%s]', $partitionId));
        }

        return $partitionId;
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
            loggedAt: $this->rowReader->time($row['lat']),
            createdAt: $this->rowReader->time($row['cat']),
            updatedAt: $this->rowReader->time($row['uat'])
        );
    }
}
