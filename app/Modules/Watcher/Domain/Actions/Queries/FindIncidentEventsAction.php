<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Queries;

use App\Modules\Watcher\Domain\Services\WatcherIncidentEventFactory;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Repositories\Dto\WatcherIncidentEventDto;
use App\Modules\Watcher\Repositories\WatcherIncidentEventRepository;

/**
 * The events under one incident, read as the type that wrote them.
 *
 * The type is handed in rather than looked up: the document does not carry it, and both
 * callers already know it — the route names it, and the listener has the watcher.
 */
readonly class FindIncidentEventsAction
{
    public function __construct(
        private WatcherIncidentEventRepository $eventRepository,
        private WatcherIncidentEventFactory $eventFactory
    ) {
    }

    /**
     * @return WatcherIncidentEventObject[]
     */
    public function handle(
        string $incidentId,
        WatcherTypeEnum $type,
        int $page = 1,
        int $perPage = 50
    ): array {
        return array_map(
            fn(WatcherIncidentEventDto $dto): WatcherIncidentEventObject => $this->eventFactory->make($dto, $type),
            $this->eventRepository->findByIncidentId($incidentId, $page, $perPage)
        );
    }
}
