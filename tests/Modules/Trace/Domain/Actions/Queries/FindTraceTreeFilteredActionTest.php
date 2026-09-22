<?php

namespace Tests\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeFilteredAction;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;
use App\Modules\Trace\Parameters\TraceTreeFilterParameters;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * A filter over a tree too large to send whole: the matches and the paths above them.
 */
class FindTraceTreeFilteredActionTest extends TestCase
{
    /**
     * top ── a ── a1 (match)
     *    │    └─ a2
     *    └─ b ── b1 ── b11 (match)
     */
    public function testMatchesComeWithEveryAncestorUpToTheTop(): void
    {
        $result = $this->action(matchedIds: ['a1', 'b11'], limit: 10)->handle('top', $this->parameters());

        $ids = array_map(static fn(TraceTreeRawObject $node): string => $node->traceId, $result->items);

        sort($ids);

        self::assertSame(['a', 'a1', 'b', 'b1', 'b11', 'top'], $ids);
        self::assertSame(2, $result->matchedCount);
        self::assertFalse($result->truncated);
    }

    public function testAMatchAboveAnotherIsNotReadTwice(): void
    {
        $result = $this->action(matchedIds: ['a', 'a1'], limit: 10)->handle('top', $this->parameters());

        self::assertCount(3, $result->items);
    }

    public function testMatchesPastTheLimitAreCut(): void
    {
        $result = $this->action(matchedIds: ['a1', 'b11', 'a2'], limit: 2)->handle('top', $this->parameters());

        self::assertTrue($result->truncated);
        self::assertSame(2, $result->matchedCount);
        self::assertCount(6, $result->items);
    }

    public function testNoMatchesNoNodes(): void
    {
        $result = $this->action(matchedIds: [], limit: 10)->handle('top', $this->parameters());

        self::assertSame([], $result->items);
        self::assertFalse($result->truncated);
    }

    /**
     * @param string[] $matchedIds
     */
    private function action(array $matchedIds, int $limit): FindTraceTreeFilteredAction
    {
        config()->set('module-trace.tree.filter_limit', $limit);

        $parents = [
            'top' => 'above-the-top',
            'a'   => 'top',
            'b'   => 'top',
            'a1'  => 'a',
            'a2'  => 'a',
            'b1'  => 'b',
            'b11' => 'b1',
        ];

        $nodes = [];

        foreach ($parents as $traceId => $parentTraceId) {
            $nodes[$traceId] = $this->node($traceId, $parentTraceId);
        }

        $repository = $this->createMock(TraceTreeCacheRepository::class);

        $repository->method('findFiltered')
            ->willReturnCallback(
                static fn(string $rootTraceId, TraceTreeFilterParameters $parameters, int $limit): array => array_slice(
                    array_map(static fn(string $traceId): TraceTreeRawObject => $nodes[$traceId], $matchedIds),
                    0,
                    $limit
                )
            );

        $repository->method('findByTraceIds')
            ->willReturnCallback(
                static fn(string $rootTraceId, array $traceIds): array => array_values(
                    array_intersect_key($nodes, array_flip($traceIds))
                )
            );

        return new FindTraceTreeFilteredAction($repository);
    }

    private function parameters(): TraceTreeFilterParameters
    {
        return new TraceTreeFilterParameters(serviceIds: [], types: [], tags: [], statuses: ['failed']);
    }

    private function node(string $traceId, string $parentTraceId): TraceTreeRawObject
    {
        return new TraceTreeRawObject(
            serviceId: 1,
            traceId: $traceId,
            parentTraceId: $parentTraceId,
            type: 'job',
            tags: [],
            status: 'success',
            duration: null,
            memory: null,
            cpu: null,
            loggedAt: Carbon::now(),
        );
    }
}
