<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Mutations;

use App\Modules\Watcher\Domain\Events\WatcherIncidentChangedEvent;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherTriggerObject;
use App\Modules\Watcher\Repositories\WatcherIncidentEventRepository;
use App\Modules\Watcher\Repositories\WatcherIncidentRepository;
use App\Modules\Watcher\Repositories\WatcherRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;

/**
 * Records that a watcher had something to say.
 *
 * The incident is the fact and stays open until a person closes it; the events under it
 * are every time the watcher said it again. Which is why a repeat does not open a second
 * incident: one problem, one row to close, however many times it was noticed.
 */
readonly class RegisterTriggerAction
{
    public function __construct(
        private WatcherRepository $watcherRepository,
        private WatcherIncidentRepository $incidentRepository,
        private WatcherIncidentEventRepository $eventRepository,
        private Dispatcher $events
    ) {
    }

    public function handle(WatcherObject $watcher, WatcherTriggerObject $trigger, Carbon $occurredAt): void
    {
        // The cooldown is on speaking, not on checking. The check runs every minute so
        // that a problem is noticed promptly; this is what keeps a problem that lasts an
        // hour from filling the incident with sixty identical events.
        if (!is_null($watcher->lastTriggeredAt)
            && $watcher->lastTriggeredAt->diffInSeconds($occurredAt, true) < $watcher->cooldownSeconds
        ) {
            return;
        }

        $incident = $this->incidentRepository->findLastOpenByWatcherId($watcher->id)
            ?? $this->incidentRepository->create($watcher->id, $occurredAt);

        $this->eventRepository->create($incident->id, $occurredAt, $trigger);

        $this->incidentRepository->incrementEventsCount($incident->id, $occurredAt);

        $this->watcherRepository->updateTriggeredAt($watcher->id, $occurredAt);

        // Read back rather than patched in memory: the counter was incremented by the
        // database, and whoever listens is shown the row as it now stands.
        $changed = $this->incidentRepository->findById($incident->id) ?? $incident;

        $this->events->dispatch(new WatcherIncidentChangedEvent($changed));
    }
}
