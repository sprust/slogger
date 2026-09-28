<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters;

use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;

readonly class FindMcpIncidentsParameters
{
    public function __construct(
        public ?WatcherIncidentStatusEnum $status,
        public ?int $watcherId,
        public int $page
    ) {
    }
}
