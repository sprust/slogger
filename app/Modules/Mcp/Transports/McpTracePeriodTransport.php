<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Transports;

use App\Modules\Mcp\Entities\McpTracePeriodObject;
use App\Modules\Trace\Parameters\PeriodParameters;

readonly class McpTracePeriodTransport
{
    public function toLoggingPeriod(McpTracePeriodObject $period): PeriodParameters
    {
        return new PeriodParameters(
            from: $period->from->clone(),
            to: $period->to->clone()->subMicrosecond(),
        );
    }
}
