<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

use Illuminate\Support\Carbon;

readonly class WatcherIncidentEventObject
{
    /**
     * @param array<string, scalar>            $settings
     * @param array<string, scalar>            $measured
     * @param array<int, array<string, mixed>> $groups
     */
    public function __construct(
        public string $id,
        public string $incidentId,
        public array $settings,
        public array $measured,
        public array $groups,
        public Carbon $occurredAt
    ) {
    }
}
