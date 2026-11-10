<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

use App\Modules\Mcp\Enums\McpIncidentStatusEnum;
use Illuminate\Support\Carbon;

readonly class McpIncidentDataObject
{
    public function __construct(
        public string $id,
        public int $watcherId,
        public McpIncidentStatusEnum $status,
        public Carbon $firstEventAt,
        public Carbon $lastEventAt,
        public int $eventsCount,
        public ?Carbon $closedAt,
        public ?int $closedByUserId
    ) {
    }
}
