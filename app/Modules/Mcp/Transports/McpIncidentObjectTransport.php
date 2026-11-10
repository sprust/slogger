<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Transports;

use App\Modules\Mcp\Entities\Bridges\McpIncidentDataObject;
use App\Modules\Mcp\Entities\Bridges\McpIncidentObject;
use App\Modules\Mcp\Entities\Bridges\McpIncidentWatcherObject;
use App\Modules\Mcp\Enums\McpIncidentStatusEnum;
use App\Modules\Mcp\Enums\McpWatcherTypeEnum;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Entities\WatcherObject;

readonly class McpIncidentObjectTransport
{
    public function fromWatcher(
        WatcherIncidentObject $incident,
        ?WatcherObject $watcher
    ): McpIncidentObject {
        return new McpIncidentObject(
            incident: new McpIncidentDataObject(
                id: $incident->id,
                watcherId: $incident->watcherId,
                status: McpIncidentStatusEnum::from($incident->status->value),
                firstEventAt: $incident->firstEventAt,
                lastEventAt: $incident->lastEventAt,
                eventsCount: $incident->eventsCount,
                closedAt: $incident->closedAt,
                closedByUserId: $incident->closedByUserId,
            ),
            watcher: $watcher === null
                ? null
                : new McpIncidentWatcherObject(
                    id: $watcher->id,
                    name: $watcher->name,
                    type: McpWatcherTypeEnum::from($watcher->type->value),
                    serviceIds: $watcher->match->serviceIds ?? [],
                ),
        );
    }
}
