<?php

namespace Tests\Modules\Trace\Domain\Services;

use App\Modules\Trace\Domain\Actions\Queries\IsShouldContinueBuildTraceTreeCacheAction;
use App\Modules\Trace\Domain\Events\TraceTreeCacheStateChangedEvent;
use App\Modules\Trace\Domain\Services\TraceTreeCacheBuilderService;
use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Repositories\Dto\Trace\Tree\TraceTreeCachePageDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * One slice of a build: what it writes, and what it says should happen next.
 *
 * A build is a chain of these rather than one long job, so that a delivery lives seconds
 * and no broker, reload or deploy costs more than the slice in flight.
 */
class TraceTreeCacheBuilderServiceTest extends TestCase
{
    public function testTheFirstSliceWritesTheRootAndExpandsIt(): void
    {
        $written = [];

        $service = $this->service(
            written: $written,
            children: ['root' => ['a', 'b']],
            pages: [0 => ['root']]
        );

        $slice = $service->handleSlice('root', 'v1', 0, null);

        self::assertSame(['root'], $written[0]);
        self::assertSame(['a', 'b'], $written[1]);
        self::assertFalse($slice->finished);
        self::assertSame(1, $slice->nextDepth);
        self::assertNull($slice->nextAfterId);
    }

    /**
     * A level wider than one slice is handed on with the cursor it stopped at, so the
     * next job continues the same level rather than starting it again.
     */
    public function testAFullPageAsksForTheSameDepthAfterItsLastId(): void
    {
        $written = [];

        $service = $this->service(
            written: $written,
            children: [],
            pages: [1 => array_map(static fn(int $index): string => "p-$index", range(1, 1000))],
            lastId: 'id-1000'
        );

        $slice = $service->handleSlice('root', 'v1', 1, null);

        self::assertFalse($slice->finished);
        self::assertSame(1, $slice->nextDepth);
        self::assertSame('id-1000', $slice->nextAfterId);
    }

    public function testALevelWithNoChildrenEndsTheBuildAndWritesTheAncestors(): void
    {
        $written = [];

        $service = $this->service(
            written: $written,
            children: [],
            pages: [2 => ['leaf']],
            ancestors: ['grandparent']
        );

        $slice = $service->handleSlice('root', 'v1', 2, null);

        self::assertTrue($slice->finished);
        self::assertNull($slice->nextDepth);
        self::assertSame(['grandparent'], $written[-1]);
    }

    public function testACanceledBuildStopsWithoutWriting(): void
    {
        $written = [];

        $service = $this->service(
            written: $written,
            children: ['root' => ['a']],
            pages: [0 => ['root']],
            canContinue: false
        );

        $slice = $service->handleSlice('root', 'v1', 0, null);

        self::assertTrue($slice->stopped);
        self::assertSame([], $written);
    }

    /**
     * Chunks land close together on a wide level, and a frame per chunk would replace a
     * poll every second with a stream faster than that — the throttle is what makes the
     * announcement an improvement rather than a flood.
     */
    public function testChunksInsideOneWindowAnnounceOnce(): void
    {
        $written = [];

        $events = $this->createMock(Dispatcher::class);
        $events->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(TraceTreeCacheStateChangedEvent::class));

        $this->service(
            written: $written,
            children: ['root' => ['a', 'b', 'c']],
            pages: [0 => ['root']],
            events: $events
        )->handleSlice('root', 'v1', 0, null);
    }

    /**
     * @param array<int, list<string>>    $written
     * @param array<string, list<string>> $children
     * @param array<int, list<string>>    $pages
     * @param string[]                    $ancestors
     */
    private function service(
        array &$written,
        array $children,
        array $pages,
        ?string $lastId = null,
        array $ancestors = [],
        bool $canContinue = true,
        ?Dispatcher $events = null
    ): TraceTreeCacheBuilderService {
        $traces = $this->createMock(TraceRepository::class);
        $traces->method('findOneDetailByTraceId')->willReturn($this->trace('root'));
        $traces->method('findByTraceIds')->willReturnCallback(
            fn(array $traceIds): array => array_map($this->trace(...), $traceIds)
        );

        $tree = $this->createMock(TraceTreeRepository::class);
        $tree->method('findChildrenTraceIds')->willReturnCallback(
            function (array $parentTraceIds) use ($children): array {
                $found = [];

                foreach ($parentTraceIds as $parentTraceId) {
                    foreach ($children[$parentTraceId] ?? [] as $childTraceId) {
                        $found[] = $childTraceId;
                    }
                }

                return $found === [] ? [] : [$found];
            }
        );
        $tree->method('findChainToParentTraceId')->willReturn($ancestors);

        $cache = $this->createMock(TraceTreeCacheRepository::class);
        $cache->method('createMany')->willReturnCallback(
            function (string $rootTraceId, int $depth, array $parametersList) use (&$written): void {
                foreach ($parametersList as $parameters) {
                    $written[$depth][] = $parameters->traceId;
                }
            }
        );
        $cache->method('findTraceIdsPage')->willReturnCallback(
            fn(string $rootTraceId, int $depth): TraceTreeCachePageDto => new TraceTreeCachePageDto(
                traceIds: $pages[$depth] ?? [],
                lastId: $lastId
            )
        );
        $cache->method('existsByDepth')->willReturnCallback(
            fn(string $rootTraceId, int $depth): bool => isset($pages[$depth]) || $depth === 1
        );

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
        $shouldContinue->method('handle')->willReturn($canContinue);

        return new TraceTreeCacheBuilderService(
            $traces,
            $tree,
            $cache,
            $states,
            $shouldContinue,
            $events ?? $this->createMock(Dispatcher::class)
        );
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
