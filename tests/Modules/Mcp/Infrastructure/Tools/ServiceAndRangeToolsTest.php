<?php

namespace Tests\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpDataRangeAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpServicesAction;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\GetDataRangeTool;
use App\Modules\Mcp\Infrastructure\Tools\ListServicesTool;
use App\Modules\Mcp\Infrastructure\Tools\McpToolFormatter;
use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDataRangeAction;
use App\Modules\Trace\Entities\Trace\TraceDataRangeObject;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class ServiceAndRangeToolsTest extends TestCase
{
    public function testListsAllServices(): void
    {
        $result = $this->servicesTool()->call(new McpToolArguments([]));

        $this->assertSame(
            [['id' => 1, 'name' => 'billing'], ['id' => 2, 'name' => 'auth']],
            $result->data['services']
        );
    }

    public function testSearchesServicesIgnoringCase(): void
    {
        $result = $this->servicesTool()->call(new McpToolArguments(['query' => 'BILL']));

        $this->assertSame([['id' => 1, 'name' => 'billing']], $result->data['services']);
    }

    public function testDataRange(): void
    {
        $action = $this->createMock(FindTraceDataRangeAction::class);

        $action->method('handle')->willReturn(
            new TraceDataRangeObject(
                firstHour: Carbon::parse('2026-09-25 10:00:00', 'UTC'),
                lastHour: Carbon::parse('2026-09-28 14:00:00', 'UTC')
            )
        );

        $result = new GetDataRangeTool(new FindMcpDataRangeAction($action), new McpToolFormatter())
            ->call(new McpToolArguments([]));

        $this->assertSame(
            ['first_hour' => '2026-09-25T10:00:00Z', 'last_hour' => '2026-09-28T14:00:00Z'],
            $result->data
        );
    }

    public function testEmptyDataRange(): void
    {
        $action = $this->createMock(FindTraceDataRangeAction::class);

        $action->method('handle')->willReturn(new TraceDataRangeObject(firstHour: null, lastHour: null));

        $result = new GetDataRangeTool(new FindMcpDataRangeAction($action), new McpToolFormatter())
            ->call(new McpToolArguments([]));

        $this->assertSame(['first_hour' => null, 'last_hour' => null], $result->data);
    }

    private function servicesTool(): ListServicesTool
    {
        $action = $this->createMock(FindServicesAction::class);

        $action->method('handle')->willReturn([
            new ServiceObject(id: 1, name: 'billing', apiToken: 'a'),
            new ServiceObject(id: 2, name: 'auth', apiToken: 'b'),
        ]);

        return new ListServicesTool(new FindMcpServicesAction($action));
    }
}
