<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Queries;

use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Parameters\FindIncidentsParameters;
use App\Modules\Watcher\Repositories\WatcherIncidentRepository;

readonly class FindIncidentsAction
{
    public function __construct(
        private WatcherIncidentRepository $incidentRepository
    ) {
    }

    /**
     * @return WatcherIncidentObject[]
     */
    public function handle(FindIncidentsParameters $parameters): array
    {
        return $this->incidentRepository->find($parameters);
    }
}
