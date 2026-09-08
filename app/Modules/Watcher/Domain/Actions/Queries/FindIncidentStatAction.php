<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Queries;

use App\Modules\Watcher\Entities\WatcherIncidentStatObject;
use App\Modules\Watcher\Repositories\WatcherIncidentRepository;

readonly class FindIncidentStatAction
{
    public function __construct(
        private WatcherIncidentRepository $incidentRepository
    ) {
    }

    public function handle(): WatcherIncidentStatObject
    {
        return new WatcherIncidentStatObject(
            openedCount: $this->incidentRepository->countOpen()
        );
    }
}
