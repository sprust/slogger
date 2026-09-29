<?php

namespace Tests\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpIndexStatusAction;
use App\Modules\Mcp\Enums\McpIndexStatusEnum;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexStatsAction;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexObject;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexStatsObject;
use App\Modules\Trace\Entities\Trace\TraceIndexInfoObject;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class FindMcpIndexStatusActionTest extends TestCase
{
    public function testBuildingWithProgress(): void
    {
        $status = $this->action(
            $this->index(inProcess: true),
            [
                new TraceIndexInfoObject(collectionName: 'traces_2026_09_28_10_11', name: 'dyn_sid_lat', progress: 50.0),
                new TraceIndexInfoObject(collectionName: 'traces_2026_09_28_11_12', name: 'dyn_sid_lat', progress: 30.0),
                new TraceIndexInfoObject(collectionName: 'traces_2026_09_28_10_11', name: 'dyn_other', progress: 90.0),
            ]
        )->handle('idx-1');

        $this->assertSame(McpIndexStatusEnum::Building, $status->status);
        $this->assertSame(0.2, $status->progress);
    }

    public function testBuildingNotStartedHasNoProgress(): void
    {
        $status = $this->action($this->index(inProcess: true), [])->handle('idx-1');

        $this->assertSame(McpIndexStatusEnum::Building, $status->status);
        $this->assertNull($status->progress);
    }

    public function testReady(): void
    {
        $this->assertSame(McpIndexStatusEnum::Ready, $this->action($this->index(inProcess: false), [])->handle('idx-1')->status);
    }

    public function testError(): void
    {
        $status = $this->action($this->index(inProcess: false, error: 'disk is full'), [])->handle('idx-1');

        $this->assertSame(McpIndexStatusEnum::Error, $status->status);
        $this->assertSame('disk is full', $status->error);
    }

    public function testNotFound(): void
    {
        $this->assertSame(McpIndexStatusEnum::NotFound, $this->action(null, [])->handle('idx-1')->status);
    }

    /**
     * @param TraceIndexInfoObject[] $inProcess
     */
    private function action(?TraceDynamicIndexObject $index, array $inProcess): FindMcpIndexStatusAction
    {
        $find = $this->createMock(FindTraceDynamicIndexAction::class);
        $find->method('handle')->willReturn($index);

        $stats = $this->createMock(FindTraceDynamicIndexStatsAction::class);
        $stats->method('handle')->willReturn(
            new TraceDynamicIndexStatsObject(
                inProcessCount: count($inProcess),
                errorsCount: 0,
                totalCount: 1,
                indexesInProcess: $inProcess
            )
        );

        return new FindMcpIndexStatusAction($find, $stats);
    }

    private function index(bool $inProcess, ?string $error = null): TraceDynamicIndexObject
    {
        $now = Carbon::parse('2026-09-28 12:00:00', 'UTC');

        return new TraceDynamicIndexObject(
            id: 'idx-1',
            name: 'dyn_sid_lat_traces',
            indexName: 'dyn_sid_lat',
            collectionNames: ['traces_2026_09_28_10_11', 'traces_2026_09_28_11_12', 'traces_2026_09_28_12_13', 'traces_2026_09_28_13_14'],
            fields: [],
            inProcess: $inProcess,
            created: !$inProcess,
            error: $error,
            actualUntilAt: $now,
            createdAt: $now
        );
    }
}
