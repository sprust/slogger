<?php

namespace Tests\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\CompareMcpTraceGroupsAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpServicesAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceGroupsAction;
use App\Modules\Mcp\Domain\Services\McpTraceIndexExceptionTranslator;
use App\Modules\Mcp\Domain\Services\McpTracePeriodMapper;
use App\Modules\Mcp\Domain\Services\McpTracePeriodResolver;
use App\Modules\Mcp\Infrastructure\Tools\CompareTraceGroupsTool;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\McpToolFormatter;
use App\Modules\Mcp\Infrastructure\Tools\McpToolServiceFinder;
use App\Modules\Mcp\Infrastructure\Tools\McpToolTimeParser;
use App\Modules\Mcp\Infrastructure\Tools\McpToolTraceScopeReader;
use App\Modules\Mcp\Infrastructure\Tools\TopTraceGroupsTool;
use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Trace\Domain\Actions\Queries\CompareTraceGroupsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceGroupsAction;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
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
use Throwable;

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

    public function testGroupsIndexBuilding(): void
    {
        $result = $this->topTool(new TraceDynamicIndexInProcessException('idx-1'))
            ->call(new McpToolArguments([...self::SCOPE, 'by' => ['type']]));

        $this->assertFalse($result->isError);
        $this->assertSame('index_building', $result->data['status']);
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

    public function testCompareByServiceNamesTheService(): void
    {
        $result = $this->compareTool(serviceValue: true)->call(
            new McpToolArguments([...self::SCOPE, 'group_a_statuses' => ['failed'], 'by' => 'service'])
        );

        $this->assertSame(['id' => 2, 'name' => 'pms'], $result->data['rows'][0]['value']);
    }

    public function testCompareErrors(): void
    {
        foreach (['data.', 'data.a b', 'data', 'hour'] as $by) {
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

    private function topTool(?Throwable $exception = null): TopTraceGroupsTool
    {
        $action = $this->createMock(FindTraceGroupsAction::class);

        if ($exception) {
            $action->method('handle')->willThrowException($exception);
        } else {
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
        }

        return new TopTraceGroupsTool(
            new FindMcpTraceGroupsAction($action, new McpTraceIndexExceptionTranslator(), new McpTracePeriodMapper()),
            $this->scopeReader(),
            $this->serviceFinder(),
            new McpToolFormatter()
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
            new CompareMcpTraceGroupsAction($action, new McpTraceIndexExceptionTranslator(), new McpTracePeriodMapper()),
            $this->scopeReader(),
            $this->serviceFinder(),
            new McpToolFormatter()
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
            new McpToolTimeParser(),
            $this->serviceFinder(),
            new McpTracePeriodResolver(),
            new McpToolFormatter()
        );
    }
}
