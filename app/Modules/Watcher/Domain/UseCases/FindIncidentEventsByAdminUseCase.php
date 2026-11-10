<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\UseCases;

use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentEventsAction;
use App\Modules\Watcher\Domain\Exceptions\WatcherIncidentNotFoundException;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;

readonly class FindIncidentEventsByAdminUseCase
{
    public function __construct(
        private FindIncidentAction $findIncidentAction,
        private FindWatcherAction $findWatcherAction,
        private FindIncidentEventsAction $findIncidentEventsAction,
    ) {
    }

    /**
     * @return WatcherIncidentEventObject[]
     *
     * @throws WatcherIncidentNotFoundException
     */
    public function handle(
        WatcherTypeEnum $type,
        string $incidentId,
        int $page,
        int $perPage
    ): array {
        $incident = $this->findIncidentAction->handle($incidentId);

        if ($incident === null) {
            throw new WatcherIncidentNotFoundException($incidentId);
        }

        // Historical incidents still use the settings of a soft-deleted watcher.
        $watcher = $this->findWatcherAction->handle(
            id: $incident->watcherId,
            withTrashed: true,
        );

        if ($watcher === null || $watcher->type !== $type) {
            throw new WatcherIncidentNotFoundException($incidentId);
        }

        return $this->findIncidentEventsAction->handle(
            incidentId: $incidentId,
            type: $type,
            page: $page,
            perPage: $perPage,
        );
    }
}
