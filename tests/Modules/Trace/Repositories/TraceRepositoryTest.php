<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Repositories;

use App\Modules\Trace\Repositories\Services\ClickhouseDataPathTypes;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceFilterBuilder;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceRowReader;
use App\Modules\Trace\Repositories\Services\TraceDataPathResolver;
use App\Modules\Trace\Repositories\TraceRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Services\Clickhouse\FakeClickhouseClient;

class TraceRepositoryTest extends TestCase
{
    private FakeClickhouseClient $client;

    public function testPageOfTracesNewestFirst(): void
    {
        $traces = $this->repository([[$this->row()]])->find(
            page: 3,
            perPage: 20,
            serviceIds: [1],
            loggedAtFrom: Carbon::parse('2026-09-29 10:00:00', 'UTC')
        );

        $query = $this->client->selects[0];

        $this->assertStringContainsString('FROM traces FINAL WHERE sid IN {f0:Array(UInt32)} AND lat >= ', $query['sql']);
        $this->assertStringEndsWith('ORDER BY lat DESC, tid LIMIT {limit:UInt32} OFFSET {offset:UInt32}', $query['sql']);
        $this->assertSame(20, $query['params']['limit']);
        $this->assertSame(40, $query['params']['offset']);

        $trace = $traces[0];

        $this->assertSame('trace-1', $trace->traceId);
        $this->assertSame(2, $trace->serviceId);
        $this->assertNull($trace->parentTraceId);
        $this->assertSame(['api'], $trace->tags);
        $this->assertSame(0.2, $trace->duration);
        $this->assertNull($trace->memory);
        $this->assertSame('2026-09-29 10:00:00.123456', $trace->loggedAt->format('Y-m-d H:i:s.u'));
    }

    public function testDetailKeepsTheOrderOfTheData(): void
    {
        $trace = $this->repository([[$this->row(rawData: '{"b":1,"a":{"y":null,"x":[1,2]}}')]])
            ->findOneDetailByTraceId('trace-1');

        $this->assertNotNull($trace);
        $this->assertSame(['b', 'a'], array_map(static fn($child) => $child->key, $trace->data->children ?? []));
        $this->assertSame(['a.y', 'a.x'], array_map(static fn($child) => $child->key, $trace->data->children[1]->children ?? []));
        $this->assertSame(['tid' => 'trace-1'], $this->client->selects[0]['params']);
    }

    public function testDetailOfDataThatIsNotAnObject(): void
    {
        $trace = $this->repository([[$this->row(rawData: '"just text"')]])->findOneDetailByTraceId('trace-1');

        $this->assertSame('just text', $trace?->data->value);
    }

    public function testMissingTraceReadsAsAbsent(): void
    {
        $this->assertNull($this->repository([[]])->findOneDetailByTraceId('trace-1'));
    }

    public function testTreeNodesReadTheLatestVersionOfEachTrace(): void
    {
        $nodes = $this->repository([[$this->row(parentTraceId: 'root')]])->findTreeNodesByTraceIds(['trace-1']);

        $this->assertStringContainsString('WHERE tid IN {tids:Array(String)} ORDER BY uat DESC LIMIT 1 BY sid, tid', $this->client->selects[0]['sql']);
        $this->assertSame('root', $nodes[0]->parentTraceId);
        $this->assertSame([], $this->repository()->findTreeNodesByTraceIds([]));
    }

    public function testHourRange(): void
    {
        $range = $this->repository([[['first' => '2026-09-26 10:00:00.000000', 'last' => '2026-09-29 11:00:00.000000']]])
            ->findHourRange();

        $this->assertSame('2026-09-26T10:00:00Z', $range->firstHour?->toIso8601ZuluString());
        $this->assertSame('2026-09-29T11:00:00Z', $range->lastHour?->toIso8601ZuluString());

        $empty = $this->repository([[]])->findHourRange();

        $this->assertNull($empty->firstHour);
        $this->assertNull($empty->lastHour);
    }

    public function testEndedPartitionsAreTheHoursThatEndedByTheRetentionBoundary(): void
    {
        $to = Carbon::parse('2026-09-29 12:00:00', 'UTC');

        $partitions = $this->repository([[
            ['partition_id' => '1790676000', 'rows' => 10],
            ['partition_id' => '1790679600', 'rows' => 5],
        ]])->findEndedPartitions($to);

        $query = $this->client->selects[0];

        // an hour is dropped once its end, not its start, is past the boundary
        $this->assertStringContainsString(
            "parseDateTime64BestEffort(partition, 0, 'UTC') + INTERVAL 1 HOUR <= {to:DateTime64(6, 'UTC')}",
            $query['sql']
        );
        $this->assertSame($to, $query['params']['to']);
        $this->assertSame(['1790676000', '1790679600'], array_map(static fn($partition) => $partition->id, $partitions));
        $this->assertSame([10, 5], array_map(static fn($partition) => $partition->rowsCount, $partitions));
    }

    public function testDropPartition(): void
    {
        $this->repository()->dropPartition('1790676000');

        $this->assertSame("ALTER TABLE traces DROP PARTITION ID '1790676000'", $this->client->commands[0]['sql']);
    }

    public function testEndedPartitionsRefuseAnUnexpectedId(): void
    {
        $this->expectException(RuntimeException::class);

        $this->repository([[['partition_id' => "1'; DROP", 'rows' => 1]]])
            ->findEndedPartitions(Carbon::parse('2026-09-29 12:00:00', 'UTC'));
    }

    public function testDropPartitionRefusesAnUnexpectedId(): void
    {
        $this->expectException(RuntimeException::class);

        $this->repository()->dropPartition("1790676000\n");
    }

    public function testTreeNodesAreReadInBatchesOfIds(): void
    {
        $traceIds = array_map(static fn(int $index) => "trace-$index", range(1, 12_001));

        $nodes = $this->repository([[$this->row()], [], [$this->row(parentTraceId: 'root')]])->findTreeNodesByTraceIds($traceIds);

        $this->assertSame([5000, 5000, 2001], array_map(static fn($select) => count($select['params']['tids']), $this->client->selects));
        $this->assertCount(2, $nodes);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(string $rawData = '{"code":200}', string $parentTraceId = ''): array
    {
        return [
            'sid'    => 2,
            'tid'    => 'trace-1',
            'ptid'   => $parentTraceId,
            'tp'     => 'request',
            'st'     => 'success',
            'tgs'    => ['api'],
            'dt_raw' => $rawData,
            'dur'    => 0.2,
            'mem'    => null,
            'cpu'    => null,
            'lat'    => '2026-09-29 10:00:00.123456',
            'cat'    => '2026-09-29 10:00:01.000000',
            'uat'    => '2026-09-29 10:00:02.000000',
        ];
    }

    /**
     * @param list<list<array<string, mixed>>> $answers
     */
    private function repository(array $answers = []): TraceRepository
    {
        $this->client = new FakeClickhouseClient($answers);

        $pathTypes = $this->createMock(ClickhouseDataPathTypes::class);
        $pathTypes->method('arrayPaths')->willReturn([]);

        return new TraceRepository(
            client: $this->client,
            filterBuilder: new ClickhouseTraceFilterBuilder(new TraceDataPathResolver($pathTypes)),
            rowReader: new ClickhouseTraceRowReader(),
        );
    }
}
