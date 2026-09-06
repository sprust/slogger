<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Broadcasting;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * One tree build's state, on its way to whoever has that tree open.
 *
 * ShouldBroadcastNow rather than ShouldBroadcast: the queued variant would put a job on
 * the `default` queue, taking another lap through the very consumer pool this is usually
 * published from. Publishing to the fanout is a single AMQP write and does not need a job
 * of its own.
 */
class TraceTreeStateBroadcast implements ShouldBroadcastNow
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
     * The keys of TraceTreeStateResource, exactly.
     *
     * Not a coincidence and not decoration: the panel types this frame as the `data` of
     * the cancel endpoint's response, which that resource produces. Matching the shape
     * means the frame lands in the generated type and no second, unrelated description of
     * it has to be maintained by hand — a ws payload never reaches the OpenAPI schema.
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
