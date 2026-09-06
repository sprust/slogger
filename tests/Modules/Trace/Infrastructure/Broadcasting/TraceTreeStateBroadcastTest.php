<?php

namespace Tests\Modules\Trace\Infrastructure\Broadcasting;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Infrastructure\Broadcasting\TraceTreeStateBroadcast;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreeStateResource;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * What goes over the wire, and the promise it makes to the panel.
 *
 * A ws payload never reaches the OpenAPI schema, so the panel types this frame as the
 * `data` of the endpoint that carries the same thing. That only holds while the keys
 * match, and nothing but this test notices when they stop matching — the two are written
 * in different files, in different languages, and neither is generated from the other.
 */
class TraceTreeStateBroadcastTest extends TestCase
{
    public function testTheFrameMatchesTheTreeStateResource(): void
    {
        $state = $this->state();

        $this->assertSame(
            array_keys(new TraceTreeStateResource($state)->toArray()),
            array_keys(new TraceTreeStateBroadcast($state)->broadcastWith())
        );
    }

    public function testTheFrameGoesToThatTreeAlone(): void
    {
        $broadcast = new TraceTreeStateBroadcast($this->state());

        // By the root trace id, which is not the trace that was opened: with is_child
        // false the server walks up to the topmost ancestor and builds that one.
        $this->assertSame(
            ['private-sl-trace-tree.root-trace-id'],
            array_map(strval(...), $broadcast->broadcastOn())
        );

        $this->assertSame('state.changed', $broadcast->broadcastAs());
    }

    private function state(): TraceTreeCacheStateObject
    {
        return new TraceTreeCacheStateObject(
            rootTraceId: 'root-trace-id',
            version: 'v1',
            status: TraceTreeCacheStateStatusEnum::InProcess,
            count: 12,
            error: null,
            startedAt: Carbon::parse('2026-09-06 10:00:00'),
            finishedAt: null,
            createdAt: Carbon::parse('2026-09-06 10:00:00'),
            updatedAt: Carbon::parse('2026-09-06 10:00:05'),
        );
    }
}
