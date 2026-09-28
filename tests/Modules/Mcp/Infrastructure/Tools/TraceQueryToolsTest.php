<?php

namespace Tests\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpServicesAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceDataFieldsAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceFacetsAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTracesAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpSettingsAction;
use App\Modules\Mcp\Domain\Services\McpTraceDataFilterParser;
use App\Modules\Mcp\Domain\Services\McpTraceIndexExceptionTranslator;
use App\Modules\Mcp\Domain\Services\McpTracePeriodMapper;
use App\Modules\Mcp\Domain\Services\McpTracePeriodResolver;
use App\Modules\Mcp\Entities\McpSettingsObject;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\FindTracesTool;
use App\Modules\Mcp\Infrastructure\Tools\ListTraceDataFieldsTool;
use App\Modules\Mcp\Infrastructure\Tools\McpToolFormatter;
use App\Modules\Mcp\Infrastructure\Tools\McpToolServiceFinder;
use App\Modules\Mcp\Infrastructure\Tools\McpToolTimeParser;
use App\Modules\Mcp\Infrastructure\Tools\McpToolTraceScopeReader;
use App\Modules\Mcp\Infrastructure\Tools\TraceFacetsTool;
use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Trace\Domain\Actions\Queries\FindStatusesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTagsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDetailAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTracesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTypesAction;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Entities\Trace\Data\TraceDataAdditionalFieldObject;
use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;
use App\Modules\Trace\Parameters\TraceFindParameters;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Mcp\McpTraceQueryTestTrait;
use Throwable;

class TraceQueryToolsTest extends TestCase
{
    use McpTraceQueryTestTrait;

    private const array SCOPE = ['service_ids' => [2], 'from' => '2026-09-28T10:20:00Z', 'to' => '2026-09-28T12:05:00Z'];

    private ?TraceFindParameters $captured = null;

    public function testFacets(): void
    {
        $result = $this->facetsTool()->call(new McpToolArguments([...self::SCOPE, 'statuses' => ['failed']]));

        $this->assertFalse($result->isError);
        $this->assertSame(
            [
                'from'      => '2026-09-28T10:00:00Z',
                'to'        => '2026-09-28T13:00:00Z',
                'types'     => [['value' => 'request', 'count' => 30], ['value' => 'job', 'count' => 4]],
                'statuses'  => [['value' => 'failed', 'count' => 3]],
                'tags'      => [],
                'truncated' => ['types' => false, 'statuses' => false, 'tags' => false],
            ],
            $result->data
        );
    }

    public function testFacetsIndexBuilding(): void
    {
        $result = $this->facetsTool(new TraceDynamicIndexInProcessException('idx-1'))
            ->call(new McpToolArguments(self::SCOPE));

        $this->assertFalse($result->isError);
        $this->assertSame('index_building', $result->data['status']);
        $this->assertSame('idx-1', $result->data['index_id']);
    }

    public function testFindTraces(): void
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
        $this->assertSame('2026-09-28T10:00:00Z', $result->data['from']);
        $this->assertSame(1, $result->data['page']);
        $this->assertFalse($result->data['has_more']);

        $trace = $result->data['traces'][0];

        $this->assertSame(
            ['trace_id', 'parent_trace_id', 'type', 'status', 'tags', 'duration', 'memory', 'cpu', 'logged_at', 'data'],
            array_keys($trace)
        );
        $this->assertSame('t1', $trace['trace_id']);
        $this->assertSame('2026-09-28T12:30:00Z', $trace['logged_at']);
        $this->assertSame('{"request.uri":"\/api\/pay"}', json_encode($trace['data']));
        $this->assertSame(['failed'], $this->captured?->statuses);
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
        $result = $this->findTool(traceIds: array_map(static fn(int $i) => "t$i", range(1, 20)))
            ->call(new McpToolArguments([...self::SCOPE, 'page' => 2]));

        $this->assertTrue($result->data['has_more']);
        $this->assertSame(2, $result->data['page']);
        $this->assertSame(2, $this->captured?->page);
    }

    public function testFindTracesErrors(): void
    {
        $this->assertToolError('invalid_data_filter', $this->findTool(), [...self::SCOPE, 'data_filter' => ['status ~ 5']]);
        $this->assertToolError(
            'tags_with_data_filter',
            $this->findTool(),
            [...self::SCOPE, 'tags' => ['api'], 'data_filter' => ['user.id exists']]
        );
        $this->assertToolError('index_error', $this->findTool(exception: new TraceDynamicIndexErrorException('broken')), self::SCOPE);
        $this->assertNull($this->captured);
    }

    public function testPeriodRuleErrors(): void
    {
        $this->assertToolError('invalid_time', $this->findTool(), [...self::SCOPE, 'from' => 'yesterday'], '"from"');
        $this->assertToolError('invalid_time', $this->findTool(), [...self::SCOPE, 'to' => '2026-09-28'], '"to"');
        $this->assertToolError('invalid_period', $this->findTool(), [...self::SCOPE, 'from' => '2026-09-28T13:00:00Z']);
        $this->assertToolError(
            'period_too_wide',
            $this->facetsTool(),
            [...self::SCOPE, 'from' => '2026-09-27T06:00:00Z'],
            'get_trace_metrics'
        );
        $this->assertToolError('service_not_found', $this->findTool(), [...self::SCOPE, 'service_ids' => [2, 999]], '999');
        $this->assertNull($this->captured);
    }

    public function testDataFields(): void
    {
        $result = $this->dataFieldsTool()->call(new McpToolArguments([...self::SCOPE, 'type' => 'request']));

        $this->assertSame(
            [
                'from'         => '2026-09-28T10:00:00Z',
                'to'           => '2026-09-28T13:00:00Z',
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
        /** @var FindTracesTool|TraceFacetsTool $tool */
        $result = $tool->call(new McpToolArguments($arguments));

        $this->assertTrue($result->isError, $error);
        $this->assertSame($error, $result->data['error']);

        if (!is_null($hintPart)) {
            $this->assertStringContainsString($hintPart, $result->data['hint']);
        }
    }

    private function facetsTool(?Throwable $exception = null): TraceFacetsTool
    {
        $types = $this->createMock(FindTypesAction::class);

        if ($exception) {
            $types->method('handle')->willThrowException($exception);
        } else {
            $types->method('handle')->willReturn([
                new TraceStringFieldObject(name: 'job', count: 4),
                new TraceStringFieldObject(name: 'request', count: 30),
            ]);
        }

        $statuses = $this->createMock(FindStatusesAction::class);
        $statuses->method('handle')->willReturn([new TraceStringFieldObject(name: 'failed', count: 3)]);

        $tags = $this->createMock(FindTagsAction::class);
        $tags->method('handle')->willReturn([]);

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

        return new TraceFacetsTool(
            new FindMcpTraceFacetsAction(
                $types,
                $statuses,
                $tags,
                new McpTraceIndexExceptionTranslator(),
                new McpTracePeriodMapper()
            ),
            $settings,
            $this->scopeReader(),
            new McpToolFormatter()
        );
    }

    /**
     * @param string[] $traceIds
     */
    private function findTool(array $traceIds = ['t1'], ?Throwable $exception = null): FindTracesTool
    {
        $search = $this->createMock(FindTracesAction::class);

        if ($exception) {
            $search->method('handle')->willThrowException($exception);
        } else {
            $search->method('handle')->willReturnCallback(function (TraceFindParameters $parameters) use ($traceIds) {
                $this->captured = $parameters;

                return $this->traceItems(
                    $traceIds,
                    [new TraceDataAdditionalFieldObject(key: 'request.uri', values: ['/api/pay'])]
                );
            });
        }

        return new FindTracesTool(
            new FindMcpTracesAction(
                $search,
                new McpTraceDataFilterParser(),
                new McpTraceIndexExceptionTranslator(),
                new McpTracePeriodMapper()
            ),
            $this->scopeReader(),
            new McpToolFormatter()
        );
    }

    private function dataFieldsTool(): ListTraceDataFieldsTool
    {
        $search = $this->createMock(FindTracesAction::class);
        $search->method('handle')->willReturn($this->traceItems(['t1']));

        $detail = $this->createMock(FindTraceDetailAction::class);
        $detail->method('handle')->willReturn(
            $this->detailWithData('t1', [$this->dataNode('request', null, [$this->dataNode('request.uri', '/api/pay')])])
        );

        return new ListTraceDataFieldsTool(
            new FindMcpTraceDataFieldsAction(
                $search,
                $detail,
                new McpTraceIndexExceptionTranslator(),
                new McpTracePeriodMapper()
            ),
            $this->scopeReader(),
            new McpToolFormatter()
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
            new McpToolTimeParser(),
            new McpToolServiceFinder(new FindMcpServicesAction($services)),
            new McpTracePeriodResolver(),
            new McpToolFormatter()
        );
    }
}
