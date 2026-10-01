<?php

namespace Tests\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\CompareMcpTraceGroupsAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpServicesAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceGroupsAction;
use App\Modules\Mcp\Domain\Services\McpTraceDataFilterParser;
use App\Modules\Mcp\Domain\Services\McpTracePeriodMapper;
use App\Modules\Mcp\Domain\Services\McpTracePeriodResolver;
use App\Modules\Mcp\Infrastructure\Tools\CompareTraceGroupsTool;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\McpToolFormatter;
use App\Modules\Mcp\Infrastructure\Tools\McpToolServiceFinder;
use App\Modules\Mcp\Infrastructure\Tools\McpToolTimeParser;
use App\Modules\Mcp\Infrastructure\Tools\McpToolTraceScopeReader;
use App\Modules\Mcp\Infrastructure\Tools\AggregateTracesTool;
use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Trace\Domain\Actions\Queries\CompareTraceGroupsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceGroupsAction;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonRowObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupsObject;
use App\Modules\Trace\Enums\TraceCompareByEnum;
use App\Modules\Trace\Enums\TraceGroupFieldEnum;
use App\Modules\Trace\Parameters\TraceCompareGroupsParameters;
use App\Modules\Trace\Parameters\TraceFindGroupsParameters;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TraceGroupToolsTest extends TestCase
{
    private const array SCOPE = ['from' => '2026-09-28T10:00:00Z', 'to' => '2026-09-28T11:00:00Z'];

    private ?TraceFindGroupsParameters $groupsParameters     = null;
    private ?TraceCompareGroupsParameters $compareParameters = null;

    public function testTypesByService(): void
    {
        $result = $this->topTool()->call(
            new McpToolArguments([...self::SCOPE, 'service_ids' => [2], 'by' => ['service', 'type'], 'statuses' => ['failed']])
        );

        $this->assertFalse($result->isError);
        $this->assertSame(
            [
                'service'          => ['id' => 2, 'name' => 'pms'],
                'type'             => 'request',
                'count'            => 12,
                'duration_avg'     => 0.5,
                'duration_p95'     => 1.25,
                'duration_max'     => 2.0,
                'example_trace_id' => 'slow-1',
            ],
            $result->data['groups'][0]
        );
        $this->assertFalse($result->data['truncated']);
        $this->assertSame([TraceGroupFieldEnum::Service, TraceGroupFieldEnum::Type], $this->groupsParameters?->groupBy);
        $this->assertSame(100, $this->groupsParameters->limit);
        $this->assertSame([2], $this->groupsParameters->serviceIds);
        $this->assertSame(['failed'], $this->groupsParameters->statuses);
        $this->assertSame('2026-09-28 10:59:59', $this->groupsParameters->loggingPeriod->to?->toDateTimeString());
    }

    public function testTimeWindow(): void
    {
        $result = $this->topTool()->call(new McpToolArguments([...self::SCOPE, 'by' => ['minute10']]));

        $this->assertSame(
            ['minute10', 'count', 'duration_avg', 'duration_p95', 'duration_max', 'example_trace_id'],
            array_keys($result->data['groups'][0])
        );
        $this->assertSame('2026-09-28T10:10:00Z', $result->data['groups'][0]['minute10']);
    }

    /**
     * @return array<string, array{string[]}>
     */
    public static function invalidGroupBy(): array
    {
        return [
            'two times' => [['hour', 'minute10']],
            'repeated'  => [['type', 'type']],
            'unknown'   => [['tag']],
            'too many'  => [['service', 'type', 'status', 'hour']],
        ];
    }

    /**
     * @param string[] $by
     */
    #[DataProvider('invalidGroupBy')]
    public function testInvalidGroupBy(array $by): void
    {
        $result = $this->topTool()->call(new McpToolArguments([...self::SCOPE, 'by' => $by]));

        $this->assertTrue($result->isError);
        $this->assertSame('invalid_group_by', $result->data['error']);
        $this->assertNull($this->groupsParameters);
    }

    public function testGroupsWithDataFilter(): void
    {
        $result = $this->topTool()->call(
            new McpToolArguments([...self::SCOPE, 'by' => ['type'], 'data_filter' => ['response.status >= 500']])
        );

        $this->assertFalse($result->isError);
        $this->assertCount(1, $this->groupsParameters?->data?->filter ?? []);
        $this->assertSame('dt.response.status', $this->groupsParameters->data->filter[0]->field);
        $this->assertSame(500.0, (float) $this->groupsParameters->data->filter[0]->numeric?->value);
    }

    public function testGroupsWithoutDataFilterAreNotFilteredByData(): void
    {
        $this->topTool()->call(new McpToolArguments([...self::SCOPE, 'by' => ['type']]));

        $this->assertNotNull($this->groupsParameters);
        $this->assertNull($this->groupsParameters->data);
    }

    public function testInvalidDataFilter(): void
    {
        $result = $this->topTool()->call(
            new McpToolArguments([...self::SCOPE, 'by' => ['type'], 'data_filter' => ['status ~ 5']])
        );

        $this->assertTrue($result->isError);
        $this->assertSame('invalid_data_filter', $result->data['error']);
        $this->assertStringContainsString('status ~ 5', $result->data['hint']);
        $this->assertNull($this->groupsParameters);
    }

    public function testWholeRetentionIsAggregated(): void
    {
        $result = $this->topTool()->call(
            new McpToolArguments(['from' => '2026-09-25T11:00:00Z', 'to' => '2026-09-28T11:00:00Z', 'by' => ['type']])
        );

        $this->assertFalse($result->isError);
        $this->assertSame('2026-09-25T11:00:00Z', $result->data['from']);
        $this->assertSame('2026-09-28T11:00:00Z', $result->data['to']);
        $this->assertSame('2026-09-25 11:00:00', $this->groupsParameters?->loggingPeriod->from?->toDateTimeString());
    }

    public function testCompareByType(): void
    {
        $result = $this->compareTool()->call(
            new McpToolArguments([...self::SCOPE, 'group_a_statuses' => ['failed'], 'by' => 'type'])
        );

        $this->assertFalse($result->isError);
        $this->assertSame(10, $result->data['group_a_total']);
        $this->assertSame(
            ['value' => 'request', 'group_a_count' => 8, 'group_a_share' => 0.8, 'group_b_count' => 10, 'group_b_share' => 0.1],
            $result->data['rows'][0]
        );
        $this->assertSame(TraceCompareByEnum::Type, $this->compareParameters?->by);
        $this->assertSame([], $this->compareParameters->groupBStatuses);
        $this->assertSame(30, $this->compareParameters->limit);
    }

    public function testCompareByDataKey(): void
    {
        $this->compareTool()->call(
            new McpToolArguments([...self::SCOPE, 'group_a_statuses' => ['failed'], 'by' => 'data.response.status'])
        );

        $this->assertSame(TraceCompareByEnum::Data, $this->compareParameters?->by);
        $this->assertSame('response.status', $this->compareParameters->dataKey);
    }

    public function testCompareByADataKeyNamedDt(): void
    {
        $this->compareTool()->call(
            new McpToolArguments([...self::SCOPE, 'group_a_statuses' => ['failed'], 'by' => 'data.dt.x'])
        );

        $this->assertSame('dt.x', $this->compareParameters?->dataKey);
    }

    public function testDataFilterWithARestrictedKeyIsAToolError(): void
    {
        foreach (['user-agent exists', "a'b exists", 'a..b exists'] as $condition) {
            $result = $this->topTool()->call(
                new McpToolArguments([...self::SCOPE, 'by' => ['type'], 'data_filter' => [$condition]])
            );

            $this->assertSame('invalid_data_filter', $result->data['error'] ?? null, $condition);
        }

        $this->assertNull($this->groupsParameters);
    }

    public function testCompareByServiceNamesTheService(): void
    {
        $result = $this->compareTool(serviceValue: true)->call(
            new McpToolArguments([...self::SCOPE, 'group_a_statuses' => ['failed'], 'by' => 'service'])
        );

        $this->assertSame(['id' => 2, 'name' => 'pms'], $result->data['rows'][0]['value']);
    }

    public function testCompareErrors(): void
    {
        foreach (['data.', 'data.a b', 'data', 'hour', 'data.a-b', "data.a'b", 'data.a..b', "data.abc\n"] as $by) {
            $result = $this->compareTool()->call(
                new McpToolArguments([...self::SCOPE, 'group_a_statuses' => ['failed'], 'by' => $by])
            );

            $this->assertSame('invalid_group_by', $result->data['error'] ?? null, $by);
        }

        $result = $this->compareTool()->call(
            new McpToolArguments([
                ...self::SCOPE,
                'group_a_statuses' => ['failed'],
                'group_b_statuses' => ['failed', 'success'],
                'by'               => 'type',
            ])
        );

        $this->assertSame('overlapping_groups', $result->data['error'] ?? null);
        $this->assertStringContainsString('failed', $result->data['hint']);
        $this->assertNull($this->compareParameters);
    }

    private function topTool(): AggregateTracesTool
    {
        $action = $this->createMock(FindTraceGroupsAction::class);
        $action->method('handle')->willReturnCallback(function (TraceFindGroupsParameters $parameters) {
            $this->groupsParameters = $parameters;

            return new TraceGroupsObject(
                items: [
                    new TraceGroupObject(
                        serviceId: 2,
                        type: 'request',
                        status: 'failed',
                        startedAt: Carbon::parse('2026-09-28 10:10:00', 'UTC'),
                        count: 12,
                        durationAvg: 0.5,
                        durationP95: 1.25,
                        durationMax: 2.0,
                        exampleTraceId: 'slow-1'
                    ),
                ],
                truncated: false
            );
        });

        return new AggregateTracesTool(
            findMcpTraceGroupsAction: new FindMcpTraceGroupsAction(
                findTraceGroupsAction: $action,
                dataFilterParser: new McpTraceDataFilterParser(),
                periodMapper: new McpTracePeriodMapper()
            ),
            scopeReader: $this->scopeReader(),
            serviceFinder: $this->serviceFinder(),
            formatter: new McpToolFormatter()
        );
    }

    private function compareTool(bool $serviceValue = false): CompareTraceGroupsTool
    {
        $action = $this->createMock(CompareTraceGroupsAction::class);
        $action->method('handle')->willReturnCallback(function (TraceCompareGroupsParameters $parameters) use ($serviceValue) {
            $this->compareParameters = $parameters;

            return new TraceGroupComparisonObject(
                groupATotal: 10,
                groupBTotal: 100,
                rows: [
                    new TraceGroupComparisonRowObject(
                        value: $serviceValue ? 2 : 'request',
                        groupACount: 8,
                        groupAShare: 0.8,
                        groupBCount: 10,
                        groupBShare: 0.1
                    ),
                ],
                truncated: false
            );
        });

        return new CompareTraceGroupsTool(
            compareMcpTraceGroupsAction: new CompareMcpTraceGroupsAction(
                compareTraceGroupsAction: $action,
                periodMapper: new McpTracePeriodMapper()
            ),
            scopeReader: $this->scopeReader(),
            serviceFinder: $this->serviceFinder(),
            formatter: new McpToolFormatter()
        );
    }

    private function serviceFinder(): McpToolServiceFinder
    {
        $services = $this->createMock(FindServicesAction::class);
        $services->method('handle')->willReturn([new ServiceObject(id: 2, name: 'pms', apiToken: 'a')]);

        return new McpToolServiceFinder(new FindMcpServicesAction($services));
    }

    private function scopeReader(): McpToolTraceScopeReader
    {
        return new McpToolTraceScopeReader(
            timeParser: new McpToolTimeParser(),
            serviceFinder: $this->serviceFinder(),
            periodResolver: new McpTracePeriodResolver(maxHours: 72),
            formatter: new McpToolFormatter()
        );
    }
}
