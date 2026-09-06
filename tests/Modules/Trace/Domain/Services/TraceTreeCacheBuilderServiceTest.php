<?php

namespace Tests\Modules\Trace\Domain\Services;

use App\Modules\Trace\Domain\Actions\Queries\IsShouldContinueBuildTraceTreeCacheAction;
use App\Modules\Trace\Domain\Events\TraceTreeCacheStateChangedEvent;
use App\Modules\Trace\Domain\Services\TraceTreeCacheBuilderService;
use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * How often a build says how far it has got.
 *
 * The panel used to ask once a second. Announcing every chunk would be worse than that,
 * not better: chunks land close together on a wide tree, so the throttle is what makes
 * this an improvement rather than a stream.
 */
class TraceTreeCacheBuilderServiceTest extends TestCase
{
    public function testChunksInsideOneWindowAnnounceOnce(): void
    {
        $events = $this->createMock(Dispatcher::class);

        // Three chunks, all of them handled within microseconds of each other — the whole
        // build fits inside a single throttle window, so it gets a single frame.
        $events->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(TraceTreeCacheStateChangedEvent::class));

        $this->service($events, chunks: [['a'], ['b'], ['c']])->handle('root', 'v1');
    }

    public function testABuildWithNoChildrenAnnouncesNothing(): void
    {
        $events = $this->createMock(Dispatcher::class);

        // Nothing was written beyond the root, so there is no progress to report — the
        // finish itself is announced by BuildTraceTreeCacheAction, not from here.
        $events->expects($this->never())->method('dispatch');

        $this->service($events, chunks: [])->handle('root', 'v1');
    }

    /**
     * @param list<list<string>> $chunks the child id chunks the tree walk yields
     */
    private function service(Dispatcher $events, array $chunks): TraceTreeCacheBuilderService
    {
        $traces = $this->createMock(TraceRepository::class);
        $traces->method('findOneDetailByTraceId')->willReturn($this->trace('root'));
        $traces->method('findByTraceIds')->willReturnCallback(
            fn(array $traceIds): array => array_map($this->trace(...), $traceIds)
        );

        $tree = $this->createMock(TraceTreeRepository::class);
        $tree->method('findTraceIdsInTreeByParentTraceId')->willReturn($chunks);
        $tree->method('findChainToParentTraceId')->willReturn([]);

        $cache = $this->createMock(TraceTreeCacheRepository::class);

        $states = $this->createMock(TraceTreeCacheStateRepository::class);
        $states->method('incrementCount')->willReturn(true);
        $states->method('findOneByRootTraceId')->willReturn(
            new TraceTreeCacheStateObject(
                rootTraceId: 'root',
                version: 'v1',
                status: TraceTreeCacheStateStatusEnum::InProcess,
                count: 3,
                error: null,
                startedAt: Carbon::now(),
                finishedAt: null,
                createdAt: Carbon::now(),
                updatedAt: Carbon::now(),
            )
        );

        $shouldContinue = $this->createMock(IsShouldContinueBuildTraceTreeCacheAction::class);
        $shouldContinue->method('handle')->willReturn(true);

        return new TraceTreeCacheBuilderService($traces, $tree, $cache, $states, $shouldContinue, $events);
    }

    private function trace(string $traceId): TraceDto
    {
        return new TraceDto(
            id: "id-$traceId",
            serviceId: 1,
            traceId: $traceId,
            parentTraceId: null,
            type: 'http',
            status: 'success',
            tags: [],
            data: new TraceDataObject(key: 'root', value: null, children: null, canBeFiltered: false),
            duration: 1.0,
            memory: 1.0,
            cpu: 1.0,
            hasProfiling: false,
            loggedAt: Carbon::now(),
            createdAt: Carbon::now(),
            updatedAt: Carbon::now(),
        );
    }
}
