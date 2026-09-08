<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Queries;

use App\Modules\Watcher\Entities\WatcherIncidentEventObject;
use App\Modules\Watcher\Repositories\WatcherIncidentEventRepository;

readonly class FindIncidentEventsAction
{
    public function __construct(
        private WatcherIncidentEventRepository $eventRepository
    ) {
    }

    /**
     * @return WatcherIncidentEventObject[]
     */
    public function handle(string $incidentId, int $page = 1, int $perPage = 50): array
    {
        return $this->eventRepository->findByIncidentId($incidentId, $page, $perPage);
    }
}
