<?php

namespace Tests\Modules\Trace\Infrastructure\Broadcasting;

use App\Modules\Trace\Domain\Events\TraceDynamicIndexBuiltEvent;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexStatsObject;
use App\Modules\Trace\Entities\Trace\TraceIndexInfoObject;
use App\Modules\Trace\Infrastructure\Broadcasting\TraceDynamicIndexBuiltBroadcast;
use App\Modules\Trace\Infrastructure\Broadcasting\TraceDynamicIndexStatsBroadcast;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceDynamicIndexStatsResource;
use PHPUnit\Framework\TestCase;

/**
 * What goes over the wire, and the promise it makes to the panel.
 *
 * A ws payload never reaches the OpenAPI schema, so the panel types the stats frame as
 * the `data` of the stats endpoint. That only holds while the keys match, and nothing but
 * this test notices when they stop matching — the two are written in different files, in
 * different languages, and neither is generated from the other.
 */
class TraceDynamicIndexBroadcastsTest extends TestCase
{
    public function testTheStatsFrameMatchesTheStatsResource(): void
    {
        $stats = $this->stats();

        $this->assertSame(
            array_keys(new TraceDynamicIndexStatsResource($stats)->toArray()),
            array_keys(new TraceDynamicIndexStatsBroadcast($stats)->broadcastWith())
        );
    }

    public function testTheStatsFrameCarriesTheProgressOfEachIndex(): void
    {
        $payload = new TraceDynamicIndexStatsBroadcast($this->stats())->broadcastWith();

        $this->assertSame(
            [
                [
                    'collectionName' => 'traces_2026_08',
                    'name'           => 'dyn_name_coll',
                    'progress'       => 42.5,
                ],
            ],
            $payload['indexes_in_process']
        );
    }

    public function testTheBuiltFrameGoesToThatIndexAlone(): void
    {
        $broadcast = new TraceDynamicIndexBuiltBroadcast(
            new TraceDynamicIndexBuiltEvent(indexId: 'index-id', created: true, error: null)
        );

        // Per index rather than shared: several requests can be blocked at once, each on a
        // different index, and a shared channel would wake all of them for each.
        $this->assertSame(
            ['private-sl-trace-index.index-id'],
            array_map(strval(...), $broadcast->broadcastOn())
        );

        $this->assertSame('index.built', $broadcast->broadcastAs());

        // The id is what the 412 body named, which is how the waiting request found this
        // channel in the first place.
        $this->assertSame(
            ['id' => 'index-id', 'created' => true, 'error' => null],
            $broadcast->broadcastWith()
        );
    }

    private function stats(): TraceDynamicIndexStatsObject
    {
        return new TraceDynamicIndexStatsObject(
            inProcessCount: 1,
            errorsCount: 0,
            totalCount: 4,
            indexesInProcess: [
                new TraceIndexInfoObject(
                    collectionName: 'traces_2026_08',
                    name: 'dyn_name_coll',
                    progress: 42.5,
                ),
            ],
        );
    }
}
