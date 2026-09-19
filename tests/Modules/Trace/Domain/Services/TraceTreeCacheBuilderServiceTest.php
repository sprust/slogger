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
     * The walk keeps no frontier of its own: a level is read back from the cache it has
     * just written, by the depth it was written with. What it writes one level deeper is
     * therefore the next level, and a level that produced nothing ends the walk.
     */
    public function testALevelIsReadBackFromTheCacheAndWrittenOneDeeper(): void
    {
        $children = [
            'root' => ['a', 'b'],
            'a'    => ['c'],
        ];

        /** @var array<int, list<string>> $written */
        $written = [];

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
        $tree->method('findChainToParentTraceId')->willReturn(['ancestor']);

        $cache = $this->createMock(TraceTreeCacheRepository::class);
        $cache->method('createMany')->willReturnCallback(
            function (string $rootTraceId, int $depth, array $parametersList) use (&$written): void {
                foreach ($parametersList as $parameters) {
                    $written[$depth][] = $parameters->traceId;
                }
            }
        );
        $cache->method('findTraceIdsByDepth')->willReturnCallback(
            function (string $rootTraceId, int $depth) use (&$written): array {
                return isset($written[$depth]) ? [$written[$depth]] : [];
            }
        );

        $states = $this->createMock(TraceTreeCacheStateRepository::class);
        $states->method('incrementCount')->willReturn(true);

        $shouldContinue = $this->createMock(IsShouldContinueBuildTraceTreeCacheAction::class);
        $shouldContinue->method('handle')->willReturn(true);

        $service = new TraceTreeCacheBuilderService(
            $traces,
            $tree,
            $cache,
            $states,
            $shouldContinue,
            $this->createMock(Dispatcher::class)
        );

        $service->handle('root', 'v1');

        self::assertSame(['root'], $written[0]);
        self::assertSame(['a', 'b'], $written[1]);
        self::assertSame(['c'], $written[2]);
        self::assertArrayNotHasKey(3, $written);

        // the chain above the root is cached at a depth the walk never asks for, so it
        // is never expanded upwards
        self::assertSame(['ancestor'], $written[-1]);
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
        $tree->method('findChildrenTraceIds')->willReturnCallback(
            fn(array $parentTraceIds): array => ($parentTraceIds === ['root']) ? $chunks : []
        );
        $tree->method('findChainToParentTraceId')->willReturn([]);

        $cache = $this->createMock(TraceTreeCacheRepository::class);
        $cache->method('findTraceIdsByDepth')->willReturnCallback(
            fn(string $rootTraceId, int $depth): array => match ($depth) {
                0       => [['root']],
                1       => array_map(static fn(array $chunk): array => $chunk, $chunks),
                default => [],
            }
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
