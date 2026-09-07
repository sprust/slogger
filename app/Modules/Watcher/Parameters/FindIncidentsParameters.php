<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Parameters;

use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;

readonly class FindIncidentsParameters
{
    public function __construct(
        public ?WatcherIncidentStatusEnum $status = null,
        public ?int $watcherId = null,
        public int $page = 1,
        public int $perPage = 50
    ) {
    }
}
