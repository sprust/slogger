<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Repositories;

use App\Models\Watchers\WatcherIncident;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Parameters\FindIncidentsParameters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

readonly class WatcherIncidentRepository
{
    /**
     * @return WatcherIncidentObject[]
     */
    public function find(FindIncidentsParameters $parameters): array
    {
        return WatcherIncident::query()
            ->when(
                !is_null($parameters->status),
                fn(Builder $query) => $query->where('status', $parameters->status?->value)
            )
            ->when(
                !is_null($parameters->watcherId),
                fn(Builder $query) => $query->where('watcher_id', $parameters->watcherId)
            )
            // Still open first, then newest: the list is a work queue, and a closed
            // incident is history whatever its date.
            ->orderByRaw('status = ? desc', [WatcherIncidentStatusEnum::Opened->value])
            ->orderByDesc('id')
            ->forPage($parameters->page, $parameters->perPage)
            ->get()
            ->map(fn(WatcherIncident $incident) => $this->makeObject($incident))
            ->all();
    }

    public function findLastOpenByWatcherId(int $watcherId): ?WatcherIncidentObject
    {
        // Annotated because the ordering is forwarded to the query builder, which hands
        // back a plain object where the model was: the same reason phpstan.neon already
        // carries an ignore for the collection map below.
        /** @var WatcherIncident|null $incident */
        $incident = WatcherIncident::query()
            ->where('watcher_id', $watcherId)
            ->where('status', WatcherIncidentStatusEnum::Opened->value)
            ->orderByDesc('id')
            ->first();

        return is_null($incident) ? null : $this->makeObject($incident);
    }

    public function findById(int $id): ?WatcherIncidentObject
    {
        $incident = WatcherIncident::query()->find($id);

        return $incident instanceof WatcherIncident ? $this->makeObject($incident) : null;
    }

    public function countOpen(): int
    {
        return WatcherIncident::query()
            ->where('status', WatcherIncidentStatusEnum::Opened->value)
            ->count();
    }

    public function create(int $watcherId, Carbon $occurredAt): WatcherIncidentObject
    {
        $incident = new WatcherIncident();

        $incident->watcher_id     = $watcherId;
        $incident->status         = WatcherIncidentStatusEnum::Opened->value;
        $incident->first_event_at = $occurredAt;
        $incident->last_event_at  = $occurredAt;
        $incident->events_count   = 0;

        $incident->saveOrFail();

        return $this->makeObject($incident);
    }

    public function registerEvent(int $id, Carbon $occurredAt): void
    {
        // increment rather than a read-modify-write: the counter is a denormalisation of
        // the events table, and the database is the only thing that can add to it without
        // a window in which somebody else's event is lost.
        WatcherIncident::query()
            ->where('id', $id)
            ->increment('events_count', 1, ['last_event_at' => $occurredAt]);
    }

    public function updateStatus(
        int $id,
        WatcherIncidentStatusEnum $status,
        ?Carbon $closedAt,
        ?int $closedByUserId
    ): void {
        WatcherIncident::query()
            ->where('id', $id)
            ->update([
                'status'            => $status->value,
                'closed_at'         => $closedAt,
                'closed_by_user_id' => $closedByUserId,
            ]);
    }

    private function makeObject(WatcherIncident $incident): WatcherIncidentObject
    {
        return new WatcherIncidentObject(
            id: $incident->id,
            watcherId: $incident->watcher_id,
            status: WatcherIncidentStatusEnum::from($incident->status),
            firstEventAt: $incident->first_event_at,
            lastEventAt: $incident->last_event_at,
            eventsCount: $incident->events_count,
            closedAt: $incident->closed_at,
            closedByUserId: $incident->closed_by_user_id
        );
    }
}
