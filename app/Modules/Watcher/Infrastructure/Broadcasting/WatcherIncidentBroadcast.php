<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Broadcasting;

use App\Modules\Watcher\Entities\WatcherIncidentObject;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;

/**
 * An incident was opened, added to, or closed.
 *
 * One channel for every watcher, unlike the per-index channels of the aggregator: the
 * header's badge follows all of them at once, and a channel per watcher would mean
 * subscribing to a list that changes while the panel is open.
 *
 * The frame carries both what changed and the count after it. The count is what the badge
 * shows, and reading it here rather than letting each tab ask for it keeps the answer the
 * same for everyone watching. Which incident it was is what tells a list that is open to
 * refresh — the same frame serves both.
 *
 * ShouldRescue: the incident is recorded either way. A bus that is down must not turn a
 * watcher's check, or a person closing an incident, into a failure.
 */
class WatcherIncidentBroadcast implements ShouldBroadcastNow, ShouldRescue
{
    public function __construct(
        private readonly WatcherIncidentObject $incident,
        private readonly int $openedCount
    ) {
    }

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sl-watchers'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'incident.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'incident_id'  => $this->incident->id,
            'watcher_id'   => $this->incident->watcherId,
            'status'       => $this->incident->status->value,
            'opened_count' => $this->openedCount,
        ];
    }
}
