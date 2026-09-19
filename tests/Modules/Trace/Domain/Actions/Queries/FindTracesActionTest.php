<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceServicesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTracesAction;
use App\Modules\Trace\Domain\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use App\Modules\Trace\Entities\Trace\TraceServicesObject;
use App\Modules\Trace\Parameters\TraceFindParameters;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class FindTracesActionTest extends TestCase
{
    public function testATreeIsSearchedInBatchesAndTheAnswerStaysInOrder(): void
    {
        $batches = [];

        $traces = $this->createMock(TraceRepository::class);
        $traces->method('findOneDetailByTraceId')->willReturn($this->trace('child', 5));
        $traces->method('find')->willReturnCallback(
            function (...$arguments) use (&$batches): array {
                $traceIds = $arguments[3];

                $batches[] = $traceIds;

                return match ($traceIds) {
                    ['a', 'b'] => [$this->trace('a', 9), $this->trace('b', 4)],
                    ['c', 'd'] => [$this->trace('c', 7), $this->trace('d', 1)],
                    default    => [],
                };
            }
        );

        $action = $this->action(
            $traces,
            treeCacheExists: true,
            treeIdsBatches: [['a', 'b'], ['c', 'd']]
        );

        $found = $action->handle(
            new TraceFindParameters(
                page: 1,
                perPage: 3,
                traceId: 'child',
                allTracesInTree: true
            )
        );

        self::assertSame(
            ['a', 'c', 'b'],
            array_map(
                static fn($item): string => $item->trace->traceId,
                $found->items
            ),
            'the page is ordered by logged-at across batches, not batch by batch'
        );

        self::assertSame([['a', 'b'], ['c', 'd']], $batches);
    }

    public function testASecondPageTakesWhatTheMergeSkipped(): void
    {
        $traces = $this->createMock(TraceRepository::class);
        $traces->method('findOneDetailByTraceId')->willReturn($this->trace('child', 5));
        $traces->method('find')->willReturnCallback(
            fn(...$arguments): array => match ($arguments[3]) {
                ['a', 'b'] => [$this->trace('a', 9), $this->trace('b', 4)],
                ['c', 'd'] => [$this->trace('c', 7), $this->trace('d', 1)],
                default    => [],
            }
        );

        $found = $this->action(
            $traces,
            treeCacheExists: true,
            treeIdsBatches: [['a', 'b'], ['c', 'd']]
        )->handle(
            new TraceFindParameters(
                page: 2,
                perPage: 2,
                traceId: 'child',
                allTracesInTree: true
            )
        );

        self::assertSame(
            ['b', 'd'],
            array_map(
                static fn($item): string => $item->trace->traceId,
                $found->items
            )
        );
    }

    /**
     * A tree nobody has cached is still walked, which is what this did before the cache
     * became the source: the search must not answer an empty page for it.
     */
    public function testATreeWithoutACacheIsStillWalked(): void
    {
        $asked = null;

        $traces = $this->createMock(TraceRepository::class);
        $traces->method('findOneDetailByTraceId')->willReturn($this->trace('child', 5));
        $traces->method('find')->willReturnCallback(
            function (...$arguments) use (&$asked): array {
                $asked = $arguments[3];

                return [$this->trace('a', 9)];
            }
        );

        $tree = $this->createMock(TraceTreeRepository::class);
        $tree->method('findParentTraceId')->willReturn('root');
        $tree->method('findTraceIdsInTreeByParentTraceId')->willReturn([['a']]);

        $found = $this->action($traces, treeCacheExists: false, treeIdsBatches: [], tree: $tree)->handle(
            new TraceFindParameters(
                page: 1,
                perPage: 2,
                traceId: 'child',
                allTracesInTree: true
            )
        );

        self::assertSame(['root', 'a'], $asked);
        self::assertCount(1, $found->items);
    }

    /**
     * @param list<list<string>> $treeIdsBatches
     */
    private function action(
        TraceRepository $traces,
        bool $treeCacheExists,
        array $treeIdsBatches,
        ?TraceTreeRepository $tree = null
    ): FindTracesAction {
        if ($tree === null) {
            $tree = $this->createMock(TraceTreeRepository::class);
            $tree->method('findParentTraceId')->willReturn('root');
        }

        $treeCache = $this->createMock(TraceTreeCacheRepository::class);
        $treeCache->method('existsByRootTraceId')->willReturn($treeCacheExists);
        $treeCache->method('findTraceIds')->willReturn($treeIdsBatches);

        $services = $this->createMock(FindTraceServicesAction::class);
        $services->method('handle')->willReturn(new TraceServicesObject(services: []));

        return new FindTracesAction(
            $traces,
            $tree,
            $treeCache,
            $services,
            $this->createMock(TraceDynamicIndexInitializer::class)
        );
    }

    private function trace(string $traceId, int $loggedAtMinute): TraceDto
    {
        return new TraceDto(
            id: "id-$traceId",
            serviceId: null,
            traceId: $traceId,
            parentTraceId: null,
            type: 'http',
            status: 'success',
            tags: [],
            data: new TraceDataObject(key: '', value: null, children: null, canBeFiltered: false),
            duration: 1.0,
            memory: 1.0,
            cpu: 1.0,
            hasProfiling: false,
            loggedAt: Carbon::parse('2026-09-19 12:00:00')->addMinutes($loggedAtMinute),
            createdAt: Carbon::parse('2026-09-19 12:00:00'),
            updatedAt: Carbon::parse('2026-09-19 12:00:00'),
        );
    }
}
