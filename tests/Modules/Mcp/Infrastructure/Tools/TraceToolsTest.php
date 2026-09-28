<?php

namespace Tests\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceTreeAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceTreeFilteredAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpSettingsAction;
use App\Modules\Mcp\Domain\Services\McpTraceTreeNodeFactory;
use App\Modules\Mcp\Entities\McpSettingsObject;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArgumentsException;
use App\Modules\Mcp\Infrastructure\Tools\FindInTraceTreeTool;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceDataTool;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceTool;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceTreeTool;
use App\Modules\Mcp\Infrastructure\Tools\McpToolFormatter;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDetailAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceServicesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeChildrenAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeFilteredAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeStateAction;
use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use App\Modules\Trace\Entities\Trace\TraceDetailObject;
use App\Modules\Trace\Entities\Trace\TraceServiceObject;
use App\Modules\Trace\Entities\Trace\TraceServicesObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeChildrenObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeFilteredObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeResultObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeStateObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TraceToolsTest extends TestCase
{
    private FindTraceTreeStateAction&MockObject $stateAction;
    private FindTraceTreeAction&MockObject $treeAction;
    private FindTraceTreeChildrenAction&MockObject $childrenAction;
    private FindTraceTreeFilteredAction&MockObject $filteredAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateAction    = $this->createMock(FindTraceTreeStateAction::class);
        $this->treeAction     = $this->createMock(FindTraceTreeAction::class);
        $this->childrenAction = $this->createMock(FindTraceTreeChildrenAction::class);
        $this->filteredAction = $this->createMock(FindTraceTreeFilteredAction::class);
    }

    public function testGetTraceHasNoData(): void
    {
        $result = new GetTraceTool($this->traceBridge($this->detail()), new McpToolFormatter())
            ->call(new McpToolArguments(['trace_id' => 't1']));

        $this->assertFalse($result->isError);
        $this->assertArrayNotHasKey('data', $result->data);
        $this->assertSame(['id' => 3, 'name' => 'billing'], $result->data['service']);
        $this->assertSame('2026-09-28T12:00:00Z', $result->data['logged_at']);
    }

    public function testUnknownTraceIsAToolError(): void
    {
        $result = new GetTraceTool($this->traceBridge(null), new McpToolFormatter())
            ->call(new McpToolArguments(['trace_id' => 'nope']));

        $this->assertTrue($result->isError);
        $this->assertSame('trace_not_found', $result->data['error']);
    }

    public function testTraceDataIsWhatTheUiGets(): void
    {
        $detail = $this->detail();

        $result = new GetTraceDataTool($this->traceBridge($detail), new McpToolFormatter())
            ->call(new McpToolArguments(['trace_id' => 't1']));

        $this->assertFalse($result->truncate);
        $this->assertSame(
            [
                'key'             => '',
                'value'           => null,
                'children'        => [
                    ['key' => 'url', 'value' => '/pay', 'children' => null, 'can_be_filtered' => true],
                ],
                'can_be_filtered' => false,
            ],
            $result->data['data']
        );
    }

    public function testFirstOpeningStartsTheBuild(): void
    {
        $this->stateAction->method('handle')->willReturn(new TraceTreeStateObject('root', null));
        $this->treeAction->expects($this->once())
            ->method('handle')
            ->with('t1', false, false)
            ->willReturn(new TraceTreeResultObject(state: $this->state(TraceTreeCacheStateStatusEnum::InProcess), items: null));
        $this->childrenAction->expects($this->never())->method('handle');

        $result = $this->treeTool()->call(new McpToolArguments(['trace_id' => 't1']));

        $this->assertFalse($result->isError);
        $this->assertSame('tree_building', $result->data['status']);
        $this->assertSame(10, $result->data['retry_after_seconds']);
    }

    public function testBuildingTreeIsNotRestarted(): void
    {
        $this->stateAction->method('handle')->willReturn(
            new TraceTreeStateObject('root', $this->state(TraceTreeCacheStateStatusEnum::InProcess))
        );
        $this->treeAction->expects($this->never())->method('handle');

        $result = $this->treeTool()->call(new McpToolArguments(['trace_id' => 't1']));

        $this->assertSame('tree_building', $result->data['status']);
    }

    public function testFailedTreeIsAToolErrorWithoutRebuild(): void
    {
        $this->stateAction->method('handle')->willReturn(
            new TraceTreeStateObject('root', $this->state(TraceTreeCacheStateStatusEnum::Failed, error: 'boom'))
        );
        $this->treeAction->expects($this->never())->method('handle');

        $result = $this->treeTool()->call(new McpToolArguments(['trace_id' => 't1']));

        $this->assertTrue($result->isError);
        $this->assertSame('tree_failed', $result->data['error']);
        $this->assertStringContainsString('SLogger UI', $result->data['hint']);
    }

    public function testReadyTreeIsReadBranchByBranch(): void
    {
        $this->stateAction->method('handle')->willReturn(
            new TraceTreeStateObject('root', $this->state(TraceTreeCacheStateStatusEnum::Finished))
        );
        $this->childrenAction->expects($this->once())
            ->method('handle')
            ->with('root', 'p1', 'c1', 300)
            ->willReturn(
                new TraceTreeChildrenObject(
                    items: [$this->node('n1', serviceId: 3)],
                    childrenCounts: ['n1' => 4],
                    nextCursor: 'c2'
                )
            );

        $result = $this->treeTool()->call(
            new McpToolArguments(['trace_id' => 't1', 'parent_trace_id' => 'p1', 'cursor' => 'c1'])
        );

        $this->assertSame('ready', $result->data['status']);
        $this->assertSame('c2', $result->data['next_cursor']);
        $this->assertSame(
            [
                'trace_id'        => 'n1',
                'parent_trace_id' => 'p1',
                'service'         => ['id' => 3, 'name' => 'billing'],
                'type'            => 'http',
                'status'          => 'failed',
                'duration'        => 1.5,
                'children_count'  => 4,
            ],
            $result->data['nodes'][0]
        );
    }

    public function testFindKeepsOnlyMatchedNodes(): void
    {
        $this->stateAction->method('handle')->willReturn(
            new TraceTreeStateObject('root', $this->state(TraceTreeCacheStateStatusEnum::Finished))
        );
        $this->filteredAction->method('handle')->willReturn(
            new TraceTreeFilteredObject(
                items: [$this->node('root', status: 'success'), $this->node('n1'), $this->node('n2')],
                matchedCount: 2,
                truncated: false
            )
        );

        $result = $this->findTool()->call(new McpToolArguments(['trace_id' => 't1', 'statuses' => ['failed']]));

        $this->assertSame(['n1', 'n2'], array_column($result->data['nodes'], 'trace_id'));
        $this->assertSame(2, $result->data['matched_count']);
        $this->assertFalse($result->data['truncated']);
        $this->assertArrayNotHasKey('children_count', $result->data['nodes'][0]);
    }

    public function testFindNeedsAFilter(): void
    {
        $this->expectException(McpToolArgumentsException::class);

        $this->findTool()->call(new McpToolArguments(['trace_id' => 't1']));
    }

    public function testFindDoesNotStartTheBuild(): void
    {
        $this->stateAction->method('handle')->willReturn(new TraceTreeStateObject('root', null));
        $this->treeAction->expects($this->never())->method('handle');
        $this->filteredAction->expects($this->never())->method('handle');

        $result = $this->findTool()->call(new McpToolArguments(['trace_id' => 't1', 'statuses' => ['failed']]));

        $this->assertSame('tree_building', $result->data['status']);
        $this->assertStringContainsString('get_trace_tree', $result->data['hint']);
    }

    private function traceBridge(?TraceDetailObject $detail): FindMcpTraceAction
    {
        $action = $this->createMock(FindTraceDetailAction::class);

        $action->method('handle')->willReturn($detail);

        return new FindMcpTraceAction($action);
    }

    private function treeTool(): GetTraceTreeTool
    {
        return new GetTraceTreeTool(
            new FindMcpTraceTreeAction($this->stateAction, $this->treeAction, $this->childrenAction, $this->nodeFactory()),
            $this->settings(),
            new McpToolFormatter()
        );
    }

    private function findTool(): FindInTraceTreeTool
    {
        return new FindInTraceTreeTool(
            new FindMcpTraceTreeFilteredAction($this->stateAction, $this->filteredAction, $this->nodeFactory()),
            $this->settings(),
            new McpToolFormatter()
        );
    }

    private function nodeFactory(): McpTraceTreeNodeFactory
    {
        $services = $this->createMock(FindTraceServicesAction::class);

        $services->method('handle')->willReturn(
            new TraceServicesObject([new TraceServiceObject(id: 3, name: 'billing')])
        );

        return new McpTraceTreeNodeFactory($services);
    }

    private function settings(): FindMcpSettingsAction
    {
        $action = $this->createMock(FindMcpSettingsAction::class);

        $action->method('handle')->willReturn(
            new McpSettingsObject(
                serverName: 'local',
                serverVersion: '1.0.0',
                appUrl: 'http://localhost',
                endpointUrl: 'http://localhost/mcp',
                maxStringLength: 500,
                treeNodesLimit: 300,
                listTtlMs: 3_600_000
            )
        );

        return $action;
    }

    private function state(TraceTreeCacheStateStatusEnum $status, ?string $error = null): TraceTreeCacheStateObject
    {
        $now = Carbon::parse('2026-09-28 12:00:00');

        return new TraceTreeCacheStateObject(
            rootTraceId: 'root',
            version: 'v1',
            status: $status,
            count: 10,
            error: $error,
            startedAt: $now,
            finishedAt: null,
            createdAt: $now,
            updatedAt: $now
        );
    }

    private function node(string $traceId, ?int $serviceId = null, string $status = 'failed'): TraceTreeRawObject
    {
        return new TraceTreeRawObject(
            serviceId: $serviceId,
            traceId: $traceId,
            parentTraceId: 'p1',
            type: 'http',
            tags: [],
            status: $status,
            duration: 1.5,
            memory: null,
            cpu: null,
            loggedAt: Carbon::parse('2026-09-28 12:00:00')
        );
    }

    private function detail(): TraceDetailObject
    {
        $now = Carbon::parse('2026-09-28 12:00:00', 'UTC');

        return new TraceDetailObject(
            id: 'id-t1',
            service: new TraceServiceObject(id: 3, name: 'billing'),
            traceId: 't1',
            parentTraceId: null,
            type: 'http',
            status: 'success',
            tags: ['api'],
            data: new TraceDataObject(
                key: '',
                value: null,
                children: [new TraceDataObject(key: 'url', value: '/pay', children: null, canBeFiltered: true)],
                canBeFiltered: false
            ),
            duration: 0.5,
            memory: 12.0,
            cpu: 1.0,
            hasProfiling: false,
            loggedAt: $now,
            createdAt: $now,
            updatedAt: $now
        );
    }
}
