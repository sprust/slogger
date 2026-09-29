<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Repositories;

use App\Modules\Trace\Enums\TraceMetricFieldAggregatorEnum;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Repositories\Dto\Trace\Data\TraceMetricDataFieldsFilterDto;
use App\Modules\Trace\Repositories\Dto\Trace\Data\TraceMetricFieldsFilterDto;
use App\Modules\Trace\Repositories\Services\ClickhouseDataPathTypes;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceFilterBuilder;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceRowReader;
use App\Modules\Trace\Repositories\Services\TraceDataPathResolver;
use App\Modules\Trace\Repositories\Services\TraceTimestampMetricsFactory;
use App\Modules\Trace\Repositories\TraceContentRepository;
use App\Modules\Trace\Repositories\TraceTimestampsRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Services\Clickhouse\FakeClickhouseClient;

/**
 * The charts, the filter values and the tree walk: what each asks ClickHouse and how the
 * answer is read back.
 */
class TraceReadRepositoriesTest extends TestCase
{
    /**
     * @return array<string, array{TraceTimestampEnum, string}>
     */
    public static function bucketProvider(): array
    {
        return [
            's5'    => [TraceTimestampEnum::S5, 'toStartOfInterval(lat, INTERVAL 5 SECOND)'],
            'min10' => [TraceTimestampEnum::Min10, 'toStartOfInterval(lat, INTERVAL 10 MINUTE)'],
            'h4'    => [TraceTimestampEnum::H4, 'toStartOfInterval(lat, INTERVAL 4 HOUR)'],
            'd'     => [TraceTimestampEnum::D, 'toStartOfDay(lat)'],
            'm'     => [TraceTimestampEnum::M, 'toStartOfMonth(lat)'],
        ];
    }

    #[DataProvider('bucketProvider')]
    public function testTimestampsGroupByTheStep(TraceTimestampEnum $timestamp, string $bucket): void
    {
        $client = new FakeClickhouseClient([[]]);

        $this->timestampsRepository($client)->find(
            loggedAtFrom: Carbon::parse('2026-09-29 10:00:00', 'UTC'),
            loggedAtTo: Carbon::parse('2026-09-29 11:00:00', 'UTC'),
            timestamp: $timestamp,
            fields: [new TraceMetricFieldsFilterDto(TraceMetricFieldEnum::Count, [TraceMetricFieldAggregatorEnum::Sum])]
        );

        $this->assertStringContainsString("SELECT toDateTime64($bucket, 6, 'UTC') AS bucket", $client->selects[0]['sql']);
    }

    public function testTimestampsReadEveryIndicatorOfEveryField(): void
    {
        $client = new FakeClickhouseClient([[
            ['bucket' => '2026-09-29 10:00:00.000000', 'a0' => 12, 'a1' => 0.25, 'a2' => 0.9, 'a3' => null],
        ]]);

        $result = $this->timestampsRepository($client)->find(
            loggedAtFrom: Carbon::parse('2026-09-29 10:00:00', 'UTC'),
            loggedAtTo: Carbon::parse('2026-09-29 10:00:00', 'UTC'),
            timestamp: TraceTimestampEnum::Min,
            fields: [
                new TraceMetricFieldsFilterDto(TraceMetricFieldEnum::Count, [TraceMetricFieldAggregatorEnum::Sum]),
                new TraceMetricFieldsFilterDto(
                    TraceMetricFieldEnum::Duration,
                    [TraceMetricFieldAggregatorEnum::Avg, TraceMetricFieldAggregatorEnum::P95]
                ),
            ],
            dataFields: [new TraceMetricDataFieldsFilterDto('response.size', [TraceMetricFieldAggregatorEnum::Max])]
        );

        $sql = $client->selects[0]['sql'];

        $this->assertStringContainsString('count() AS a0, avg(dur) AS a1, quantile(0.95)(dur) AS a2', $sql);
        $this->assertStringContainsString("max(if(dynamicType(dt.`response`.`size`)", $sql);
        // the last step runs to its end
        $this->assertSame('2026-09-29 10:00:59.999999', $client->selects[0]['params']['f1']->format('Y-m-d H:i:s.u'));

        $bucket = $result->timestamps[0];

        $this->assertSame('2026-09-29T10:00:00Z', $bucket->timestamp->toIso8601ZuluString());
        $this->assertSame(['count', 'dur', 'dt.response.size'], array_map(static fn($field) => $field->field, $bucket->indicators));
        $this->assertSame(12.0, $bucket->indicators[0]->indicators[0]->value);
        $this->assertSame('p95', $bucket->indicators[1]->indicators[1]->name);
        $this->assertSame(0.0, $bucket->indicators[2]->indicators[0]->value);
        $this->assertCount(3, $result->emptyIndicators);
    }

    public function testFacetsAreCountedMostFrequentFirst(): void
    {
        $client = new FakeClickhouseClient([[['name' => 'api', 'count' => 5]]]);

        $tags = new TraceContentRepository(
            client: $client,
            filterBuilder: $this->filterBuilder()
        )->findTags(
            serviceIds: [1],
            text: 'ap',
            types: ['request']
        );

        $query = $client->selects[0];

        $this->assertStringContainsString('SELECT arrayJoin(tgs) AS name, count() AS count FROM traces FINAL', $query['sql']);
        $this->assertStringContainsString('HAVING position(name, {text:String}) > 0 ORDER BY count DESC, name LIMIT {limit:UInt32}', $query['sql']);
        $this->assertSame('ap', $query['params']['text']);
        $this->assertSame(50, $query['params']['limit']);
        $this->assertSame('api', $tags[0]->name);
        $this->assertSame(5, $tags[0]->count);
    }

    public function testTreeParentIsTheTopOfTheChain(): void
    {
        $client = new FakeClickhouseClient([
            [['tid' => 'leaf', 'ptid' => 'middle']],
            [['tid' => 'middle', 'ptid' => 'root']],
            [['tid' => 'root', 'ptid' => '']],
        ]);

        $this->assertSame('root', new TraceTreeRepository($client)->findParentTraceId('leaf'));
        $this->assertSame(['tid' => 'middle'], $client->selects[1]['params']);
    }

    public function testTreeChildrenComeInBatches(): void
    {
        $client = new FakeClickhouseClient([[['tid' => 'a'], ['tid' => 'b'], ['tid' => 'c']]]);

        $batches = iterator_to_array(
            new TraceTreeRepository($client)->findChildrenTraceIds(['root'], 2),
            false
        );

        $this->assertSame([['a', 'b'], ['c']], $batches);
        $this->assertSame('SELECT DISTINCT tid FROM traces WHERE ptid IN {parents:Array(String)}', $client->selects[0]['sql']);
    }

    private function timestampsRepository(FakeClickhouseClient $client): TraceTimestampsRepository
    {
        $pathResolver = $this->pathResolver();

        return new TraceTimestampsRepository(
            client: $client,
            filterBuilder: new ClickhouseTraceFilterBuilder($pathResolver),
            rowReader: new ClickhouseTraceRowReader(),
            pathResolver: $pathResolver,
            timestampMetricsFactory: new TraceTimestampMetricsFactory(),
        );
    }

    private function filterBuilder(): ClickhouseTraceFilterBuilder
    {
        return new ClickhouseTraceFilterBuilder($this->pathResolver());
    }

    private function pathResolver(): TraceDataPathResolver
    {
        $pathTypes = $this->createMock(ClickhouseDataPathTypes::class);
        $pathTypes->method('arrayPaths')->willReturn([]);

        return new TraceDataPathResolver($pathTypes);
    }
}
