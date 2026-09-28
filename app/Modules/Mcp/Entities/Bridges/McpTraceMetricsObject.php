<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampsObjects;
use App\Modules\Trace\Enums\TraceTimestampEnum;

readonly class McpTraceMetricsObject
{
    public function __construct(
        public TraceTimestampEnum $step,
        public TraceTimestampsObjects $timestamps
    ) {
    }
}
