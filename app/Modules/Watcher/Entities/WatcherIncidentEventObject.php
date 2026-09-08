<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

use Illuminate\Support\Carbon;

readonly class WatcherIncidentEventObject
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $id,
        public string $incidentId,
        public Carbon $occurredAt,
        public array $payload
    ) {
    }
}
