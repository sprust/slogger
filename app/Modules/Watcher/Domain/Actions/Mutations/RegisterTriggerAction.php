<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Mutations;

use App\Modules\Watcher\Parameters\RegisterTriggerParameters;
use App\Modules\Watcher\Domain\Events\WatcherIncidentChangedEvent;
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Repositories\WatcherIncidentEventRepository;
use App\Modules\Watcher\Repositories\WatcherIncidentRepository;
use App\Modules\Watcher\Repositories\WatcherRepository;
use App\Modules\Common\Domain\Services\DispatcherInterface;

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
        private WatcherTypeRegistry $types,
        private DispatcherInterface $dispatcher
    ) {
    }

    public function handle(RegisterTriggerParameters $parameters): void
    {
        $watcher    = $parameters->watcher;
        $payload    = $parameters->payload;
        $occurredAt = $parameters->occurredAt;

        // The cooldown is on speaking, not on checking. The check runs every minute so
        // that a problem is noticed promptly; this is what keeps a problem that lasts an
        // hour from filling the incident with sixty identical events.
        if (
            !is_null($watcher->lastTriggeredAt)
            && $watcher->lastTriggeredAt->diffInSeconds(
                date: $occurredAt,
                absolute: true,
            ) < $watcher->cooldownSeconds
        ) {
            return;
        }

        $incident = $this->incidentRepository->findLastOpenByWatcherId($watcher->id)
            ?? $this->incidentRepository->create(
                watcherId: $watcher->id,
                occurredAt: $occurredAt,
            );

        $this->eventRepository->create(
            incidentId: $incident->id,
            occurredAt: $occurredAt,
            payload: $this->types->for($watcher->type)->eventPayloadMapper()->toDocument($payload),
        );

        $this->incidentRepository->incrementEventsCount(
            id: $incident->id,
            lastEventAt: $occurredAt,
        );

        $this->watcherRepository->updateTriggeredAt(
            id: $watcher->id,
            triggeredAt: $occurredAt,
        );

        // Read back rather than patched in memory: the counter was incremented by the
        // database, and whoever listens is shown the row as it now stands.
        $changed = $this->incidentRepository->findById($incident->id) ?? $incident;

        $this->dispatcher->event(new WatcherIncidentChangedEvent($changed));
    }
}
