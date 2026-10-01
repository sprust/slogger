<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Repositories;

use App\Modules\Trace\Repositories\TraceTreeRepository;
use PHPUnit\Framework\TestCase;
use Tests\Services\Clickhouse\FakeClickhouseClient;

class TraceTreeRepositoryTest extends TestCase
{
    public function testParentLinksInACycleEndTheWalk(): void
    {
        // root -> a -> (root, b), b -> a: each trace is met once
        $client = new FakeClickhouseClient([
            [['tid' => 'a']],
            [['tid' => 'root'], ['tid' => 'b']],
            [['tid' => 'a']],
            [['tid' => 'unexpected']],
        ]);

        $traceIds = [];

        foreach (new TraceTreeRepository($client)->findTraceIdsInTreeByParentTraceId('root', 100) as $chunk) {
            array_push($traceIds, ...$chunk);
        }

        $this->assertSame(['a', 'b'], $traceIds);
        $this->assertCount(3, $client->selects);
    }

    public function testChildrenAreAskedInBatchesOfParents(): void
    {
        $client = new FakeClickhouseClient([
            [['tid' => 'c1']],
            [['tid' => 'c2'], ['tid' => 'c3']],
            [],
        ]);

        $parentTraceIds = array_map(static fn(int $index) => "p-$index", range(1, 10_001));

        $childIds = [];

        foreach (new TraceTreeRepository($client)->findChildrenTraceIds($parentTraceIds, 2) as $chunk) {
            $childIds[] = $chunk;
        }

        $this->assertSame([[5000], [5000], [1]], array_map(static fn($select) => [count($select['params']['parents'])], $client->selects));
        $this->assertSame([['c1', 'c2'], ['c3']], $childIds);
    }
}
