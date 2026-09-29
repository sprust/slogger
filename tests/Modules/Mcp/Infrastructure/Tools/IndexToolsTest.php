<?php

namespace Tests\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpDynamicIndexesAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpIndexStatusAction;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceIndexStatusTool;
use App\Modules\Mcp\Infrastructure\Tools\GetTraceIndexesTool;
use App\Modules\Mcp\Infrastructure\Tools\McpToolFormatter;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexStatsAction;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexFieldObject;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexObject;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexStatsObject;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class IndexToolsTest extends TestCase
{
    public function testBuildingStatus(): void
    {
        $result = $this->statusTool($this->index(inProcess: true))->call(new McpToolArguments(['index_id' => 'idx-1']));

        $this->assertFalse($result->isError);
        $this->assertSame(
            ['index_id' => 'idx-1', 'status' => 'building', 'progress' => null, 'retry_after_seconds' => 10],
            $result->data
        );
    }

    public function testReadyStatus(): void
    {
        $result = $this->statusTool($this->index(inProcess: false))->call(new McpToolArguments(['index_id' => 'idx-1']));

        $this->assertSame(['index_id' => 'idx-1', 'status' => 'ready'], $result->data);
    }

    public function testErrorStatus(): void
    {
        $result = $this->statusTool($this->index(inProcess: false, error: 'disk is full'))
            ->call(new McpToolArguments(['index_id' => 'idx-1']));

        $this->assertSame(['index_id' => 'idx-1', 'status' => 'error', 'error' => 'disk is full'], $result->data);
    }

    public function testNotFoundIsNotAnError(): void
    {
        $result = $this->statusTool(null)->call(new McpToolArguments(['index_id' => 'gone']));

        $this->assertFalse($result->isError);
        $this->assertSame(['index_id' => 'gone', 'status' => 'not_found'], $result->data);
    }

    public function testListIndexes(): void
    {
        $action = $this->createMock(FindTraceDynamicIndexesAction::class);
        $action->method('handle')->willReturn([
            $this->index(inProcess: false),
            $this->index(inProcess: true, id: 'idx-2', collectionNames: ['broken']),
        ]);

        $result = new GetTraceIndexesTool(new FindMcpDynamicIndexesAction($action), new McpToolFormatter())
            ->call(new McpToolArguments([]));

        $this->assertSame(
            [
                'index_id'        => 'idx-1',
                'fields'          => [['name' => 'sid', 'title' => 'Service'], ['name' => 'lat', 'title' => 'Logged at']],
                'first_hour'      => '2026-09-28T10:00:00Z',
                'last_hour'       => '2026-09-28T12:00:00Z',
                'status'          => 'ready',
                'actual_until_at' => '2026-09-29T00:00:00Z',
            ],
            $result->data['indexes'][0]
        );
        $this->assertSame('building', $result->data['indexes'][1]['status']);
        $this->assertNull($result->data['indexes'][1]['first_hour']);
    }

    private function statusTool(?TraceDynamicIndexObject $index): GetTraceIndexStatusTool
    {
        $find = $this->createMock(FindTraceDynamicIndexAction::class);
        $find->method('handle')->willReturn($index);

        $stats = $this->createMock(FindTraceDynamicIndexStatsAction::class);
        $stats->method('handle')->willReturn(
            new TraceDynamicIndexStatsObject(inProcessCount: 0, errorsCount: 0, totalCount: 0, indexesInProcess: [])
        );

        return new GetTraceIndexStatusTool(new FindMcpIndexStatusAction($find, $stats));
    }

    /**
     * @param string[]|null $collectionNames
     */
    private function index(
        bool $inProcess,
        ?string $error = null,
        string $id = 'idx-1',
        ?array $collectionNames = null
    ): TraceDynamicIndexObject {
        return new TraceDynamicIndexObject(
            id: $id,
            name: 'dyn_sid_lat_x',
            indexName: 'dyn_sid_lat',
            collectionNames: $collectionNames ?? ['traces_2026_09_28_12_13', 'traces_2026_09_28_10_11', 'traces_2026_09_28_11_12'],
            fields: [
                new TraceDynamicIndexFieldObject(name: 'sid', title: 'Service'),
                new TraceDynamicIndexFieldObject(name: 'lat', title: 'Logged at'),
            ],
            inProcess: $inProcess,
            created: !$inProcess,
            error: $error,
            actualUntilAt: Carbon::parse('2026-09-29 00:00:00', 'UTC'),
            createdAt: Carbon::parse('2026-09-28 12:00:00', 'UTC')
        );
    }
}
