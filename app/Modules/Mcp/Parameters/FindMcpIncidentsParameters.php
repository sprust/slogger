<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters;

use App\Modules\Mcp\Enums\McpIncidentStatusEnum;

readonly class FindMcpIncidentsParameters
{
    public function __construct(
        public ?McpIncidentStatusEnum $status,
        public ?int $watcherId,
        public int $page
    ) {
    }
}
