<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Actions\Mutations;

use App\Modules\Notification\Parameters\IncidentSnapshotParameters;
use App\Modules\Notification\Enums\IncidentStatusEnum;
use App\Modules\Notification\Transports\IncidentParametersTransport;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentEventsAction;
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;

readonly class EnqueueIncidentNotificationsAction
{
    public function __construct(
        private FindWatcherAction $findWatcherAction,
        private FindIncidentEventsAction $findIncidentEventsAction,
        private WatcherTypeRegistry $watcherTypeRegistry,
        private IncidentParametersTransport $incidentParametersTransport,
        private EnqueueNotificationsAction $enqueueNotificationsAction
    ) {
    }

    public function handle(IncidentSnapshotParameters $parameters): void
    {
        $watcher = $this->findWatcherAction->handle(
            id: $parameters->watcherId,
        );

        if ($watcher === null) {
            return;
        }

        $event = $this->findIncidentEventsAction->handle(
            incidentId: $parameters->id,
            type: $watcher->type,
            page: 1,
            perPage: 1,
        )[0] ?? null;
        $watcherType = $this->watcherTypeRegistry->for($watcher->type);
        $document    = $parameters->status !== IncidentStatusEnum::Closed && $event?->payload !== null
            ? $watcherType
            ->eventPayloadMapper()
            ->toDocument($event->payload)
            : null;

        $this->enqueueNotificationsAction->handle(
            watcher: $this->incidentParametersTransport->watcher(
                watcher: $watcher,
                typeTitle: $watcherType->describe()->title,
            ),
            incident: $parameters,
            event: $this->incidentParametersTransport->event(
                event: $event,
                document: $document,
            ),
        );
    }
}
