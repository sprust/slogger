<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Entities\Bridges\McpIncidentObject;
use App\Modules\Mcp\Entities\Bridges\McpIncidentPageObject;
use App\Modules\Mcp\Parameters\FindMcpIncidentsParameters;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentsAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherAction;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Parameters\FindIncidentsParameters;

readonly class FindMcpIncidentsAction
{
    public const int PER_PAGE = 50;

    public function __construct(
        private FindIncidentsAction $findIncidentsAction,
        private FindWatcherAction $findWatcherAction
    ) {
    }

    public function handle(FindMcpIncidentsParameters $parameters): McpIncidentPageObject
    {
        $incidents = $this->findIncidentsAction->handle(
            new FindIncidentsParameters(
                status: $parameters->status,
                watcherId: $parameters->watcherId,
                page: $parameters->page,
                perPage: self::PER_PAGE
            )
        );

        $next = $this->findIncidentsAction->handle(
            new FindIncidentsParameters(
                status: $parameters->status,
                watcherId: $parameters->watcherId,
                page: $parameters->page * self::PER_PAGE + 1,
                perPage: 1
            )
        );

        $watchers = [];

        return new McpIncidentPageObject(
            items: array_map(
                function (WatcherIncidentObject $incident) use (&$watchers): McpIncidentObject {
                    if (!array_key_exists($incident->watcherId, $watchers)) {
                        $watchers[$incident->watcherId] = $this->findWatcher($incident->watcherId);
                    }

                    return new McpIncidentObject(
                        incident: $incident,
                        watcher: $watchers[$incident->watcherId]
                    );
                },
                $incidents
            ),
            hasMore: count($next) > 0
        );
    }

    private function findWatcher(int $watcherId): ?WatcherObject
    {
        return $this->findWatcherAction->handle($watcherId, withTrashed: true);
    }
}
