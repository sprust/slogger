<?php

declare(strict_types=1);

namespace App\Modules\Notification\Transports;

use App\Modules\Notification\Parameters\IncidentWatcherParameters;
use App\Modules\Notification\Parameters\IncidentEventParameters;
use App\Modules\Notification\Entities\IncidentEventGroupObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;
use App\Modules\Watcher\Entities\WatcherIncidentEventGroupObject;
use App\Modules\Watcher\Entities\Events\HasEventGroupsInterface;

readonly class IncidentParametersTransport
{
    public function watcher(WatcherObject $watcher, string $typeTitle): IncidentWatcherParameters
    {
        return new IncidentWatcherParameters(
            id: $watcher->id,
            name: $watcher->name,
            typeTitle: $typeTitle,
            notificationChannelId: $watcher->notificationChannelId,
            notifyOnOpened: $watcher->notifyOnOpened,
            notifyOnEvent: $watcher->notifyOnEvent,
            notifyOnClosed: $watcher->notifyOnClosed,
        );
    }

    /** @param array<string, mixed>|null $document */
    public function event(
        ?WatcherIncidentEventObject $event,
        ?array $document
    ): ?IncidentEventParameters {
        if ($event === null) {
            return null;
        }

        $groups = $event->payload instanceof HasEventGroupsInterface ? $event->payload->groups : [];

        return new IncidentEventParameters(
            payload: $document,
            groups: array_map(
                static fn(
                    WatcherIncidentEventGroupObject $group
                ): IncidentEventGroupObject => new IncidentEventGroupObject(
                    serviceId: $group->serviceId,
                    type: $group->type,
                    tags: $group->tags,
                    count: $group->count,
                    durationMax: $group->durationMax,
                    slowestTraceId: $group->slowestTraceId,
                    slowestTraceLoggedAt: $group->slowestTraceLoggedAt,
                ),
                $groups
            ),
        );
    }
}
