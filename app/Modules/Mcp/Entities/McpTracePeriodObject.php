<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities;

use Illuminate\Support\Carbon;

readonly class McpTracePeriodObject
{
    public function __construct(
        public Carbon $from,
        public Carbon $to
    ) {
    }
}
