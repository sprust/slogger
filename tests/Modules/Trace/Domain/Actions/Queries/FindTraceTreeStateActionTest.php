<?php

namespace Tests\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeStateAction;
use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class FindTraceTreeStateActionTest extends TestCase
{
    public function testReadsTheStateOfTheRoot(): void
    {
        $traces = $this->createMock(TraceRepository::class);
        $tree   = $this->createMock(TraceTreeRepository::class);
        $states = $this->createMock(TraceTreeCacheStateRepository::class);

        $tree->method('findParentTraceId')->with('child')->willReturn('root');
        $traces->method('findOneDetailByTraceId')->with('root')->willReturn($this->trace());
        $states->method('findOneByRootTraceId')->with('root')->willReturn($this->state());
        $states->expects($this->never())->method('reset');

        $result = new FindTraceTreeStateAction($traces, $tree, $states)->handle('child');

        $this->assertSame('root', $result?->rootTraceId);
        $this->assertSame(TraceTreeCacheStateStatusEnum::Finished, $result->state?->status);
    }

    public function testNoStateYet(): void
    {
        $traces = $this->createMock(TraceRepository::class);
        $tree   = $this->createMock(TraceTreeRepository::class);
        $states = $this->createMock(TraceTreeCacheStateRepository::class);

        $tree->method('findParentTraceId')->willReturn('root');
        $traces->method('findOneDetailByTraceId')->willReturn($this->trace());
        $states->method('findOneByRootTraceId')->willReturn(null);
        $states->expects($this->never())->method('reset');

        $result = new FindTraceTreeStateAction($traces, $tree, $states)->handle('root');

        $this->assertSame('root', $result?->rootTraceId);
        $this->assertNull($result->state);
    }

    public function testUnknownTrace(): void
    {
        $tree = $this->createMock(TraceTreeRepository::class);

        $tree->method('findParentTraceId')->willReturn(null);

        $this->assertNull(
            new FindTraceTreeStateAction(
                $this->createMock(TraceRepository::class),
                $tree,
                $this->createMock(TraceTreeCacheStateRepository::class)
            )->handle('nope')
        );
    }

    private function state(): TraceTreeCacheStateObject
    {
        $now = Carbon::parse('2026-09-28 12:00:00');

        return new TraceTreeCacheStateObject(
            rootTraceId: 'root',
            version: 'v1',
            status: TraceTreeCacheStateStatusEnum::Finished,
            count: 10,
            error: null,
            startedAt: $now,
            finishedAt: $now,
            createdAt: $now,
            updatedAt: $now
        );
    }

    private function trace(): TraceDto
    {
        $now = Carbon::parse('2026-09-28 12:00:00');

        return new TraceDto(
            id: 'id-root',
            serviceId: null,
            traceId: 'root',
            parentTraceId: null,
            type: 'http',
            status: 'success',
            tags: [],
            data: new TraceDataObject(key: '', value: null, children: null, canBeFiltered: false),
            duration: 1.0,
            memory: 1.0,
            cpu: 1.0,
            hasProfiling: false,
            loggedAt: $now,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
