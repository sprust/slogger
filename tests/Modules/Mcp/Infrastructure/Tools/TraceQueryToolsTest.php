<?php

namespace Tests\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpServicesAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceDataFieldsAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceFacetsAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTracesAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpSettingsAction;
use App\Modules\Mcp\Domain\Services\McpTraceDataFilterParser;
use App\Modules\Mcp\Domain\Services\McpTracePeriodMapper;
use App\Modules\Mcp\Domain\Services\McpTracePeriodResolver;
use App\Modules\Mcp\Entities\McpSettingsObject;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\SearchTracesTool;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceDataFieldsTool;
use App\Modules\Mcp\Infrastructure\Tools\McpToolFormatter;
use App\Modules\Mcp\Infrastructure\Tools\McpToolServiceFinder;
use App\Modules\Mcp\Infrastructure\Tools\McpToolTimeParser;
use App\Modules\Mcp\Infrastructure\Tools\McpToolTraceScopeReader;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceFacetsTool;
use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Trace\Domain\Actions\Queries\FindStatusesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTagsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDetailAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTracesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTypesAction;
use App\Modules\Trace\Entities\Trace\Data\TraceDataAdditionalFieldObject;
use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;
use App\Modules\Trace\Parameters\TraceFindParameters;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Mcp\McpTraceQueryTestTrait;

class TraceQueryToolsTest extends TestCase
{
    use McpTraceQueryTestTrait;

    private const array SCOPE = ['service_ids' => [2], 'from' => '2026-09-28T10:20:00Z', 'to' => '2026-09-28T12:05:00Z'];

    private ?TraceFindParameters $captured = null;

    private bool $facetsQueried = false;

    public function testFacets(): void
    {
        $result = $this->facetsTool()->call(new McpToolArguments([...self::SCOPE, 'statuses' => ['failed']]));

        $this->assertFalse($result->isError);
        $this->assertSame(
            [
                'from'      => '2026-09-28T10:20:00Z',
                'to'        => '2026-09-28T12:05:00Z',
                'types'     => [['value' => 'request', 'count' => 30], ['value' => 'job', 'count' => 4]],
                'statuses'  => [['value' => 'failed', 'count' => 3]],
                'tags'      => [],
                'truncated' => ['types' => false, 'statuses' => false, 'tags' => false],
            ],
            $result->data
        );
    }

    public function testSearchTraces(): void
    {
        $result = $this->findTool()->call(
            new McpToolArguments([
                ...self::SCOPE,
                'statuses'    => ['failed'],
                'data_filter' => ['response.status >= 500'],
                'data_fields' => ['request.uri'],
            ])
        );

        $this->assertFalse($result->isError);
        $this->assertSame(1, $result->data['page']);
        $this->assertFalse($result->data['has_more']);

        $trace = $result->data['traces'][0];

        $this->assertSame(
            ['trace_id', 'parent_trace_id', 'type', 'status', 'tags', 'duration', 'memory', 'cpu', 'pid', 'logged_at', 'data'],
            array_keys($trace)
        );
        $this->assertSame('t1', $trace['trace_id']);
        $this->assertSame(4321, $trace['pid']);
        $this->assertSame('2026-09-28T12:30:00Z', $trace['logged_at']);
        $this->assertSame('{"request.uri":"\/api\/pay"}', json_encode($trace['data']));
        $this->assertSame(['failed'], $this->captured?->statuses);
    }

    public function testThePeriodIsTakenExactly(): void
    {
        $result = $this->findTool()->call(new McpToolArguments(self::SCOPE));

        $this->assertFalse($result->isError);
        $this->assertSame('2026-09-28T10:20:00Z', $result->data['from']);
        $this->assertSame('2026-09-28T12:05:00Z', $result->data['to']);
        $this->assertSame('2026-09-28 10:20:00.000000', $this->captured?->loggingPeriod?->from?->format('Y-m-d H:i:s.u'));
        $this->assertSame('2026-09-28 12:04:59.999999', $this->captured->loggingPeriod->to?->format('Y-m-d H:i:s.u'));
    }

    public function testTagsAndDataFilterGoTogether(): void
    {
        $result = $this->findTool()->call(
            new McpToolArguments([...self::SCOPE, 'tags' => ['api'], 'data_filter' => ['response.status >= 500']])
        );

        $this->assertFalse($result->isError);
        $this->assertSame(['api'], $this->captured?->tags);
        $this->assertSame('dt.response.status', $this->captured->data?->filter[0]->field);
    }

    public function testWithoutServicesAllServicesAreSearched(): void
    {
        $arguments = self::SCOPE;

        unset($arguments['service_ids']);

        $result = $this->findTool()->call(new McpToolArguments($arguments));

        $this->assertFalse($result->isError);
        $this->assertSame([], $this->captured?->serviceIds);
    }

    public function testSeveralServices(): void
    {
        $this->findTool()->call(new McpToolArguments([...self::SCOPE, 'service_ids' => [2, 3]]));

        $this->assertSame([2, 3], $this->captured?->serviceIds);
    }

    public function testFullPageHasMore(): void
    {
        $result = $this->findTool(traceIds: array_map(static fn(int $i) => "t$i", range(1, FindMcpTracesAction::PER_PAGE)))
            ->call(new McpToolArguments([...self::SCOPE, 'page' => 2]));

        $this->assertTrue($result->data['has_more']);
        $this->assertSame(2, $result->data['page']);
        $this->assertSame(2, $this->captured?->page);
    }

    public function testSearchTracesErrors(): void
    {
        $this->assertToolError(
            'invalid_data_filter',
            $this->findTool(),
            [...self::SCOPE, 'data_filter' => ['status ~ 5']],
            'status ~ 5'
        );
        $this->assertNull($this->captured);
    }

    public function testPeriodRuleErrors(): void
    {
        $this->assertToolError('invalid_time', $this->findTool(), [...self::SCOPE, 'from' => 'yesterday'], '"from"');
        $this->assertToolError('invalid_time', $this->findTool(), [...self::SCOPE, 'to' => '2026-09-28'], '"to"');
        $this->assertToolError('service_not_found', $this->findTool(), [...self::SCOPE, 'service_ids' => [2, 999]], '999');
        $this->assertNull($this->captured);
    }

    public function testFromLaterThanToIsInvalid(): void
    {
        $this->assertToolError('invalid_period', $this->findTool(), [...self::SCOPE, 'from' => '2026-09-28T13:00:00Z']);
        $this->assertNull($this->captured);
    }

    public function testEmptyPeriodIsInvalid(): void
    {
        $this->assertToolError(
            'invalid_period',
            $this->findTool(),
            [...self::SCOPE, 'from' => '2026-09-28T12:05:00Z', 'to' => '2026-09-28T12:05:00Z']
        );
        $this->assertNull($this->captured);
    }

    public function testPeriodLongerThanRetentionIsTooWide(): void
    {
        $this->assertToolError(
            'period_too_wide',
            $this->facetsTool(),
            ['service_ids' => [2], 'from' => '2026-09-25T00:00:00Z', 'to' => '2026-09-28T08:00:00Z'],
            '72 hours'
        );
        $this->assertFalse($this->facetsQueried);
    }

    public function testPeriodDescriptionNamesRetention(): void
    {
        $to = array_values(
            array_filter(
                $this->findTool()->schema()->properties,
                static fn(McpToolProperty $property) => $property->name === 'to'
            )
        );

        $this->assertStringContainsString('at most 72 hours', $to[0]->description);
    }

    public function testDataFields(): void
    {
        $result = $this->dataFieldsTool()->call(new McpToolArguments([...self::SCOPE, 'type' => 'request']));

        $this->assertSame(
            [
                'from'         => '2026-09-28T10:20:00Z',
                'to'           => '2026-09-28T12:05:00Z',
                'type'         => 'request',
                'traces_count' => 1,
                'fields'       => [['key' => 'request.uri', 'example' => '/api/pay']],
            ],
            $result->data
        );
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function assertToolError(string $error, object $tool, array $arguments, ?string $hintPart = null): void
    {
        /** @var SearchTracesTool|GetTraceFacetsTool $tool */
        $result = $tool->call(new McpToolArguments($arguments));

        $this->assertTrue($result->isError, $error);
        $this->assertSame($error, $result->data['error']);

        if (!is_null($hintPart)) {
            $this->assertStringContainsString($hintPart, $result->data['hint']);
        }
    }

    private function facetsTool(): GetTraceFacetsTool
    {
        $types = $this->createMock(FindTypesAction::class);
        $types->method('handle')->willReturnCallback(function (): array {
            $this->facetsQueried = true;

            return [
                new TraceStringFieldObject(name: 'job', count: 4),
                new TraceStringFieldObject(name: 'request', count: 30),
            ];
        });

        $statuses = $this->createMock(FindStatusesAction::class);
        $statuses->method('handle')->willReturn([new TraceStringFieldObject(name: 'failed', count: 3)]);

        $tags = $this->createMock(FindTagsAction::class);
        $tags->method('handle')->willReturn([]);

        return new GetTraceFacetsTool(
            findMcpTraceFacetsAction: new FindMcpTraceFacetsAction(
                findTypesAction: $types,
                findStatusesAction: $statuses,
                findTagsAction: $tags,
                periodMapper: new McpTracePeriodMapper()
            ),
            findMcpSettingsAction: $this->settings(),
            scopeReader: $this->scopeReader()
        );
    }

    private function settings(): FindMcpSettingsAction
    {
        $settings = $this->createMock(FindMcpSettingsAction::class);
        $settings->method('handle')->willReturn(
            new McpSettingsObject(
                serverName: 'local',
                serverVersion: '1.0.0',
                appUrl: 'http://localhost',
                endpointUrl: 'http://localhost/mcp',
                maxStringLength: 500,
                treeNodesLimit: 300,
                facetsLimit: 50,
                listTtlMs: 3_600_000
            )
        );

        return $settings;
    }

    /**
     * @param string[] $traceIds
     */
    private function findTool(array $traceIds = ['t1']): SearchTracesTool
    {
        $search = $this->createMock(FindTracesAction::class);
        $search->method('handle')->willReturnCallback(function (TraceFindParameters $parameters) use ($traceIds) {
            $this->captured = $parameters;

            return $this->traceItems(
                $traceIds,
                [new TraceDataAdditionalFieldObject(key: 'request.uri', values: ['/api/pay'])]
            );
        });

        return new SearchTracesTool(
            findMcpTracesAction: new FindMcpTracesAction(
                findTracesAction: $search,
                dataFilterParser: new McpTraceDataFilterParser(),
                periodMapper: new McpTracePeriodMapper()
            ),
            scopeReader: $this->scopeReader(),
            formatter: new McpToolFormatter()
        );
    }

    private function dataFieldsTool(): GetTraceDataFieldsTool
    {
        $search = $this->createMock(FindTracesAction::class);
        $search->method('handle')->willReturn($this->traceItems(['t1']));

        $detail = $this->createMock(FindTraceDetailAction::class);
        $detail->method('handle')->willReturn(
            $this->detailWithData('t1', [$this->dataNode('request', null, [$this->dataNode('request.uri', '/api/pay')])])
        );

        return new GetTraceDataFieldsTool(
            findMcpTraceDataFieldsAction: new FindMcpTraceDataFieldsAction(
                findTracesAction: $search,
                findTraceDetailAction: $detail,
                periodMapper: new McpTracePeriodMapper()
            ),
            scopeReader: $this->scopeReader()
        );
    }

    private function scopeReader(): McpToolTraceScopeReader
    {
        $services = $this->createMock(FindServicesAction::class);
        $services->method('handle')->willReturn([
            new ServiceObject(id: 2, name: 'pms', apiToken: 'a'),
            new ServiceObject(id: 3, name: 'zb', apiToken: 'b'),
        ]);

        return new McpToolTraceScopeReader(
            timeParser: new McpToolTimeParser(),
            serviceFinder: new McpToolServiceFinder(new FindMcpServicesAction($services)),
            periodResolver: new McpTracePeriodResolver(maxHours: 72),
            formatter: new McpToolFormatter()
        );
    }
}
