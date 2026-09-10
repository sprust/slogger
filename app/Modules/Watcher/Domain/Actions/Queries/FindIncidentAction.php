<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Queries;

use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Repositories\WatcherIncidentRepository;

readonly class FindIncidentAction
{
    public function __construct(
        private WatcherIncidentRepository $incidentRepository
    ) {
    }

    public function handle(string $id): ?WatcherIncidentObject
    {
        return $this->incidentRepository->findById($id);
    }
}
