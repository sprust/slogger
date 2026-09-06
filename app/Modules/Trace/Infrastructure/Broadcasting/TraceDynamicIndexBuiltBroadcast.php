<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Broadcasting;

use App\Modules\Trace\Domain\Events\TraceDynamicIndexBuiltEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * The end of one dynamic index's build, on its way to whoever is blocked on it.
 *
 * The channel is per index rather than shared: several people can be waiting, each on a
 * different index, and a shared channel would wake all of them for each.
 */
class TraceDynamicIndexBuiltBroadcast implements ShouldBroadcastNow
{
    public function __construct(
        private readonly TraceDynamicIndexBuiltEvent $event,
    ) {
    }

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sl-trace-index.' . $this->event->indexId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'index.built';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id'      => $this->event->indexId,
            'created' => $this->event->created,
            'error'   => $this->event->error,
        ];
    }
}
