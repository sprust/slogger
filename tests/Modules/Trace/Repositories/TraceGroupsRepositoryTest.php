<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Repositories;

use App\Modules\Trace\Enums\TraceCompareByEnum;
use App\Modules\Trace\Enums\TraceGroupFieldEnum;
use App\Modules\Trace\Repositories\Services\PeriodicTraceService;
use App\Modules\Trace\Repositories\Services\TraceMetricAggregationFactory;
use App\Modules\Trace\Repositories\Services\TracePipelineBuilder;
use App\Modules\Trace\Repositories\TraceGroupsRepository;
use ArrayIterator;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use SConcur\Bson\UTCDateTime;

class TraceGroupsRepositoryTest extends TestCase
{
    private ?string $collectionName = null;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $pipeline = null;

    public function testGroupsOverAllCollectionsOfThePeriod(): void
    {
        $groups = $this->repository([
            [
                '_id'     => ['service' => 2, 'type' => 'request', 'hour' => new UTCDateTime(Carbon::parse('2026-09-28 10:00:00', 'UTC'))],
                'count'   => 12,
                'avg'     => 0.5,
                'p95'     => [1.25],
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

        $this->assertSame('traces_2026_09_28_10_11', $this->collectionName);
        $this->assertSame(['sid' => ['$in' => [2]]], array_intersect_key($this->pipeline[0]['$match'] ?? [], ['sid' => 1]));
        $this->assertSame(['st' => ['$in' => ['failed']]], array_intersect_key($this->pipeline[0]['$match'] ?? [], ['st' => 1]));
        $this->assertSame('traces_2026_09_28_11_12', $this->pipeline[1]['$unionWith']['coll'] ?? null);
        $this->assertSame([$this->pipeline[0]], $this->pipeline[1]['$unionWith']['pipeline'] ?? null);

        $group = $this->pipeline[2]['$group'] ?? [];

        $this->assertSame(
            ['service' => '$sid', 'type' => '$tp', 'hour' => ['$dateTrunc' => ['date' => '$lat', 'unit' => 'hour']]],
            $group['_id'] ?? null
        );
        $this->assertSame(['$top' => ['sortBy' => ['dur' => -1], 'output' => '$tid']], $group['example'] ?? null);
        $this->assertSame(['_id.hour' => 1, 'count' => -1, '_id' => 1], $this->pipeline[3]['$sort'] ?? null);
        $this->assertSame(['$limit' => 11], $this->pipeline[4]);

        $this->assertCount(1, $groups);
        $this->assertSame(2, $groups[0]->serviceId);
        $this->assertSame('request', $groups[0]->type);
        $this->assertNull($groups[0]->status);
        $this->assertSame('2026-09-28 10:00:00', $groups[0]->startedAt?->utc()->toDateTimeString());
        $this->assertSame(12, $groups[0]->count);
        $this->assertSame(1.25, $groups[0]->durationP95);
        $this->assertSame('slow-1', $groups[0]->exampleTraceId);
    }

    public function testTenMinutesAndSortByCountWithoutTime(): void
    {
        $repository = $this->repository([]);

        $repository->findGroups(
            loggedAtFrom: Carbon::parse('2026-09-28 10:00:00', 'UTC'),
            loggedAtTo: Carbon::parse('2026-09-28 10:59:59', 'UTC'),
            groupBy: [TraceGroupFieldEnum::Minute10],
            limit: 5
        );

        $this->assertSame(
            ['minute10' => ['$dateTrunc' => ['date' => '$lat', 'unit' => 'minute', 'binSize' => 10]]],
            $this->pipeline[1]['$group']['_id'] ?? null
        );

        $repository->findGroups(
            loggedAtFrom: Carbon::parse('2026-09-28 10:00:00', 'UTC'),
            loggedAtTo: Carbon::parse('2026-09-28 10:59:59', 'UTC'),
            groupBy: [TraceGroupFieldEnum::Status],
            limit: 5
        );

        $this->assertSame(['count' => -1, '_id' => 1], $this->pipeline[2]['$sort'] ?? null);
    }

    public function testCompareByTagUnwindsTags(): void
    {
        $counts = $this->repository([
            ['_id' => ['inA' => true, 'value' => 'api'], 'count' => 3],
            ['_id' => ['inA' => false], 'count' => 7],
        ])->compareGroups(
            loggedAtFrom: Carbon::parse('2026-09-28 10:00:00', 'UTC'),
            loggedAtTo: Carbon::parse('2026-09-28 10:59:59', 'UTC'),
            groupAStatuses: ['failed'],
            by: TraceCompareByEnum::Tag,
            dataKey: null
        );

        $this->assertSame(['$unwind' => ['path' => '$tgs', 'preserveNullAndEmptyArrays' => true]], $this->pipeline[1]);
        $this->assertSame(
            ['inA' => ['$in' => ['$st', ['failed']]], 'value' => '$tgs.nm'],
            $this->pipeline[2]['$group']['_id'] ?? null
        );
        $this->assertSame('api', $counts[0]->value);
        $this->assertTrue($counts[0]->inGroupA);
        $this->assertNull($counts[1]->value);
        $this->assertFalse($counts[1]->inGroupA);
        $this->assertSame(7, $counts[1]->count);
    }

    public function testCompareByDataKey(): void
    {
        $this->repository([])->compareGroups(
            loggedAtFrom: Carbon::parse('2026-09-28 10:00:00', 'UTC'),
            loggedAtTo: Carbon::parse('2026-09-28 10:59:59', 'UTC'),
            groupAStatuses: ['failed'],
            by: TraceCompareByEnum::Data,
            dataKey: 'response.status'
        );

        $this->assertSame('$dt.response.status', $this->pipeline[1]['$group']['_id']['value'] ?? null);
    }

    public function testNoCollectionsNoQuery(): void
    {
        $service = $this->createMock(PeriodicTraceService::class);
        $service->method('detectCollectionNames')->willReturn([]);
        $service->expects($this->never())->method('aggregate');

        $groups = new TraceGroupsRepository(new TracePipelineBuilder(), $service, new TraceMetricAggregationFactory())
            ->findGroups(
                loggedAtFrom: Carbon::parse('2026-09-28 10:00:00', 'UTC'),
                loggedAtTo: Carbon::parse('2026-09-28 10:59:59', 'UTC'),
                groupBy: [TraceGroupFieldEnum::Type],
                limit: 5
            );

        $this->assertSame([], $groups);
    }

    /**
     * @param array<int, array<string, mixed>> $documents
     */
    private function repository(array $documents): TraceGroupsRepository
    {
        $service = $this->createMock(PeriodicTraceService::class);
        $service->method('detectCollectionNames')->willReturnCallback(
            static fn(Carbon $loggedAtFrom, Carbon $loggedAtTo) => $loggedAtTo->hour === 11
                ? ['traces_2026_09_28_10_11', 'traces_2026_09_28_11_12']
                : ['traces_2026_09_28_10_11']
        );
        $service->method('aggregate')->willReturnCallback(
            function (string $collectionName, array $pipeline) use ($documents): ArrayIterator {
                $this->collectionName = $collectionName;
                $this->pipeline       = $pipeline;

                return new ArrayIterator($documents);
            }
        );

        return new TraceGroupsRepository(new TracePipelineBuilder(), $service, new TraceMetricAggregationFactory());
    }
}
