<?php

declare(strict_types=1);

namespace Tests\Factories\Entities;

use App\Modules\Watcher\Repositories\WatcherIncidentRepository;
use Illuminate\Support\Carbon;

readonly class IncidentFactory
{
    public function __construct(
        private WatcherIncidentRepository $watcherIncidentRepository,
        private WatcherFactory $watcherFactory,
    ) {
    }

    public function create(?int $watcherId = null, ?Carbon $occurredAt = null): string
    {
        return $this->watcherIncidentRepository->create(
            watcherId: $watcherId ?? $this->watcherFactory->create(),
            occurredAt: $occurredAt ?? Carbon::now(),
        )->id;
    }
}
