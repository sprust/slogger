<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Repositories;

use App\Models\Watchers\WatcherIncidentEvent;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;
use Illuminate\Support\Carbon;

readonly class WatcherIncidentEventRepository
{
    /**
     * @return WatcherIncidentEventObject[]
     */
    public function findByIncidentId(int $incidentId, int $page, int $perPage): array
    {
        return WatcherIncidentEvent::query()
            ->where('incident_id', $incidentId)
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn(WatcherIncidentEvent $event) => $this->makeObject($event))
            ->all();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(int $incidentId, Carbon $occurredAt, array $payload): WatcherIncidentEventObject
    {
        $event = new WatcherIncidentEvent();

        $event->incident_id = $incidentId;
        $event->occurred_at = $occurredAt;
        $event->payload     = $payload;

        $event->saveOrFail();

        return $this->makeObject($event);
    }

    private function makeObject(WatcherIncidentEvent $event): WatcherIncidentEventObject
    {
        return new WatcherIncidentEventObject(
            id: $event->id,
            incidentId: $event->incident_id,
            occurredAt: $event->occurred_at,
            payload: $event->payload
        );
    }
}
