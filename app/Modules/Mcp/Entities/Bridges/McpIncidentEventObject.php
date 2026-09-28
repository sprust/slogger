<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

use Illuminate\Support\Carbon;

readonly class McpIncidentEventObject
{
    /**
     * @param array<string, mixed>|null $numbers
     */
    public function __construct(
        public Carbon $occurredAt,
        public ?array $numbers
    ) {
    }
}
