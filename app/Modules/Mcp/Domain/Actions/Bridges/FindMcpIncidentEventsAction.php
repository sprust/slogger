<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Entities\Bridges\McpIncidentEventObject;
use App\Modules\Mcp\Entities\Bridges\McpIncidentEventPageObject;
use App\Modules\Mcp\Entities\Bridges\McpIncidentObject;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentEventsAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherAction;
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;

readonly class FindMcpIncidentEventsAction
{
    public const int PER_PAGE = 50;

    public function __construct(
        private FindIncidentAction $findIncidentAction,
        private FindWatcherAction $findWatcherAction,
        private FindIncidentEventsAction $findIncidentEventsAction,
        private WatcherTypeRegistry $watcherTypeRegistry
    ) {
    }

    public function handle(string $incidentId, int $page): ?McpIncidentEventPageObject
    {
        $incident = $this->findIncidentAction->handle($incidentId);

        if (is_null($incident)) {
            return null;
        }

        $watcher = $this->findWatcherAction->handle($incident->watcherId, withTrashed: true);

        if (is_null($watcher)) {
            return new McpIncidentEventPageObject(
                incident: new McpIncidentObject(incident: $incident, watcher: null),
                items: [],
                hasMore: false
            );
        }

        $mapper = $this->watcherTypeRegistry->for($watcher->type)->eventPayloadMapper();

        $events = $this->findIncidentEventsAction->handle(
            incidentId: $incidentId,
            type: $watcher->type,
            page: $page,
            perPage: self::PER_PAGE
        );

        $next = $this->findIncidentEventsAction->handle(
            incidentId: $incidentId,
            type: $watcher->type,
            page: $page * self::PER_PAGE + 1,
            perPage: 1
        );

        return new McpIncidentEventPageObject(
            incident: new McpIncidentObject(incident: $incident, watcher: $watcher),
            items: array_map(
                static fn(WatcherIncidentEventObject $event) => new McpIncidentEventObject(
                    occurredAt: $event->occurredAt,
                    numbers: is_null($event->payload) ? null : $mapper->toDocument($event->payload)
                ),
                $events
            ),
            hasMore: count($next) > 0
        );
    }
}
