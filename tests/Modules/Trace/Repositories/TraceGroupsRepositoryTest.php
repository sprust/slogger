<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Repositories;

use App\Modules\Trace\Enums\TraceCompareByEnum;
use App\Modules\Trace\Enums\TraceGroupFieldEnum;
use App\Modules\Trace\Repositories\Services\ClickhouseDataPathTypes;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceFilterBuilder;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceRowReader;
use App\Modules\Trace\Repositories\Services\TraceDataPathResolver;
use App\Modules\Trace\Repositories\TraceGroupsRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Services\Clickhouse\FakeClickhouseClient;

class TraceGroupsRepositoryTest extends TestCase
{
    private FakeClickhouseClient $client;

    public function testGroupsOfThePeriodInTimeOrder(): void
    {
        $groups = $this->repository([
            [
                'service' => 2,
                'type'    => 'request',
                'hour'    => '2026-09-28 10:00:00.000000',
                'count'   => 12,
                'avg'     => 0.5,
                'p95'     => 1.25,
                'max'     => 2.0,
                'example' => 'slow-1',
            ],
        ])->findGroups(
            loggedAtFrom: Carbon::parse('2026-09-28 10:00:00', 'UTC'),
            loggedAtTo: Carbon::parse('2026-09-28 11:59:59', 'UTC'),
            groupBy: [TraceGroupFieldEnum::Service, TraceGroupFieldEnum::Type, TraceGroupFieldEnum::Hour],
            limit: 11,
            serviceIds: [2],
            statuses: ['failed']
        );

        $query = $this->client->selects[0];

        $this->assertStringContainsString(
            "SELECT sid AS service, tp AS type, toDateTime64(toStartOfHour(lat), 6, 'UTC') AS hour, count() AS count",
            $query['sql']
        );
        $this->assertStringContainsString('argMax(tid, coalesce(dur, -1)) AS example', $query['sql']);
        $this->assertStringContainsString('sid IN {f0:Array(UInt32)}', $query['sql']);
        $this->assertStringContainsString('GROUP BY service, type, hour ORDER BY hour, count DESC, service, type', $query['sql']);
        $this->assertSame([2], $query['params']['f0']);
        $this->assertSame(11, $query['params']['limit']);

        $this->assertCount(1, $groups);
        $this->assertSame(2, $groups[0]->serviceId);
        $this->assertSame('request', $groups[0]->type);
        $this->assertNull($groups[0]->status);
        $this->assertSame('2026-09-28T10:00:00Z', $groups[0]->startedAt?->toIso8601ZuluString());
        $this->assertSame(12, $groups[0]->count);
        $this->assertSame(1.25, $groups[0]->durationP95);
        $this->assertSame('slow-1', $groups[0]->exampleTraceId);
    }

    public function testTenMinutesAndGroupsWithoutDuration(): void
    {
        $groups = $this->repository([
            [
                'minute10' => '2026-09-28 10:10:00.000000',
                'count'    => 3,
                'avg'      => null,
                'p95'      => null,
                'max'      => null,
                'example'  => '',
            ],
        ])->findGroups(
            loggedAtFrom: Carbon::parse('2026-09-28 10:00:00', 'UTC'),
            loggedAtTo: Carbon::parse('2026-09-28 10:59:59', 'UTC'),
            groupBy: [TraceGroupFieldEnum::Minute10],
            limit: 11
        );

        $this->assertStringContainsString("toDateTime64(toStartOfTenMinutes(lat), 6, 'UTC') AS minute10", $this->client->selects[0]['sql']);
        $this->assertNull($groups[0]->durationAvg);
        $this->assertNull($groups[0]->exampleTraceId);
        $this->assertSame('2026-09-28T10:10:00Z', $groups[0]->startedAt?->toIso8601ZuluString());
    }

    public function testCompareByTagCountsATraceWithoutTagsUnderNoValue(): void
    {
        $counts = $this->repository([
            ['inA' => 1, 'value' => 'api', 'count' => 4],
            ['inA' => 0, 'value' => null, 'count' => 2],
        ])->compareGroups(
            loggedAtFrom: Carbon::parse('2026-09-28 10:00:00', 'UTC'),
            loggedAtTo: Carbon::parse('2026-09-28 10:59:59', 'UTC'),
            groupAStatuses: ['failed'],
            by: TraceCompareByEnum::Tag,
            dataKey: null
        );

        $query = $this->client->selects[0];

        $this->assertStringContainsString("nullIf(arrayJoin(if(empty(tgs), [''], tgs)), '') AS value", $query['sql']);
        $this->assertSame(['failed'], $query['params']['groupA']);
        $this->assertSame(1, $query['settings']['allow_suspicious_types_in_group_by']);

        $this->assertSame('api', $counts[0]->value);
        $this->assertTrue($counts[0]->inGroupA);
        $this->assertNull($counts[1]->value);
        $this->assertFalse($counts[1]->inGroupA);
    }

    public function testCompareByDataKeyKeepsTheTypeOfTheValue(): void
    {
        $counts = $this->repository([
            ['inA' => 1, 'value' => 500, 'count' => 4],
            ['inA' => 0, 'value' => '500', 'count' => 1],
        ])->compareGroups(
            loggedAtFrom: Carbon::parse('2026-09-28 10:00:00', 'UTC'),
            loggedAtTo: Carbon::parse('2026-09-28 10:59:59', 'UTC'),
            groupAStatuses: ['failed'],
            by: TraceCompareByEnum::Data,
            dataKey: 'response.status'
        );

        $this->assertStringContainsString('dt.`response`.`status` AS value', $this->client->selects[0]['sql']);
        $this->assertSame(500, $counts[0]->value);
        $this->assertSame('500', $counts[1]->value);
    }

    public function testCompareByADataKeyNamedDt(): void
    {
        $this->repository([])->compareGroups(
            loggedAtFrom: Carbon::parse('2026-09-28 10:00:00', 'UTC'),
            loggedAtTo: Carbon::parse('2026-09-28 10:59:59', 'UTC'),
            groupAStatuses: ['failed'],
            by: TraceCompareByEnum::Data,
            dataKey: 'dt.x'
        );

        $this->assertStringContainsString('dt.`dt`.`x` AS value', $this->client->selects[0]['sql']);
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function repository(array $rows): TraceGroupsRepository
    {
        $this->client = new FakeClickhouseClient([$rows]);

        $pathTypes = $this->createMock(ClickhouseDataPathTypes::class);
        $pathTypes->method('arrayPaths')->willReturn([]);

        $pathResolver = new TraceDataPathResolver($pathTypes);

        return new TraceGroupsRepository(
            client: $this->client,
            filterBuilder: new ClickhouseTraceFilterBuilder($pathResolver),
            rowReader: new ClickhouseTraceRowReader(),
            pathResolver: $pathResolver,
        );
    }
}
