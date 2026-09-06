<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Broadcasting;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;

/**
 * One tree build's state, on its way to whoever has that tree open.
 *
 * ShouldBroadcastNow rather than ShouldBroadcast: the queued variant would put a job on
 * the `default` queue, taking another lap through the very consumer pool this is usually
 * published from. Publishing to the fanout is a single AMQP write and does not need a job
 * of its own.
 *
 * ShouldRescue because the publishing is not the caller's business: BuildTraceTreeCacheAction
 * dispatches this right after marking the build finished, and a bus that is down would
 * otherwise throw into its own catch and have the finished build recorded as failed.
 */
class TraceTreeStateBroadcast implements ShouldBroadcastNow, ShouldRescue
{
    public function __construct(
        private readonly TraceTreeCacheStateObject $state,
    ) {
    }

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sl-trace-tree.' . $this->state->rootTraceId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'state.changed';
    }

    /**
     * The keys of TraceTreeStateResource, exactly: the panel types this frame as the
     * `data` of the cancel endpoint, so matching the shape saves a hand-written type —
     * a ws payload never reaches the OpenAPI schema. Asserted by TraceTreeStateBroadcastTest.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'root_trace_id' => $this->state->rootTraceId,
            'version'       => $this->state->version,
            'status'        => $this->state->status->value,
            'count'         => $this->state->count,
            'error'         => $this->state->error,
            'started_at'    => $this->state->startedAt?->toDateTimeString(),
            'finished_at'   => $this->state->finishedAt?->toDateTimeString(),
            'created_at'    => $this->state->createdAt->toDateTimeString(),
            'updated_at'    => $this->state->updatedAt->toDateTimeString(),
        ];
    }
}
