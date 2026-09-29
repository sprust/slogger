<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

use Illuminate\Support\Carbon;

readonly class McpLogEntryObject
{
    public function __construct(
        public Carbon $time,
        public string $level,
        public string $file,
        public string $text
    ) {
    }
}
