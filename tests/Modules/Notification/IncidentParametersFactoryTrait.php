<?php

declare (strict_types=1);

namespace Tests\Modules\Notification;

use App\Modules\Notification\Parameters\IncidentSnapshotParameters;
use App\Modules\Notification\Parameters\IncidentWatcherParameters;
use App\Modules\Notification\Parameters\IncidentEventParameters;
use App\Modules\Notification\Enums\IncidentStatusEnum;
use App\Modules\Notification\Transports\IncidentParametersTransport;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use Tests\Modules\Watcher\WatcherTypeRegistryFactoryTrait;

trait IncidentParametersFactoryTrait
{
    use WatcherTypeRegistryFactoryTrait;

    private function notificationWatcher(WatcherObject $watcher): IncidentWatcherParameters
    {
        return (new IncidentParametersTransport())->watcher(
            watcher: $watcher,
            typeTitle: $this->watcherTypeRegistry()->for($watcher->type)->describe()->title,
        );
    }

    private function notificationIncident(
        WatcherIncidentObject $incident
    ): IncidentSnapshotParameters {
        return new IncidentSnapshotParameters(
            id: $incident->id,
            watcherId: $incident->watcherId,
            status: IncidentStatusEnum::from($incident->status->value),
            firstEventAt: $incident->firstEventAt,
            lastEventAt: $incident->lastEventAt,
            eventsCount: $incident->eventsCount,
            closedAt: $incident->closedAt,
            closedByUserId: $incident->closedByUserId,
        );
    }

    private function notificationEvent(
        ?WatcherIncidentEventObject $event,
        WatcherTypeEnum $type
    ): ?IncidentEventParameters {
        $document = $event?->payload === null ? null : $this
            ->watcherTypeRegistry()
            ->for($type)
            ->eventPayloadMapper()
            ->toDocument($event->payload);

        return (new IncidentParametersTransport())->event(
            event: $event,
            document: $document,
        );
    }
}
