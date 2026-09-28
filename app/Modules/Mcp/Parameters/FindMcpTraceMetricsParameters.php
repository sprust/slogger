<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters;

use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Enums\TraceTimestampPeriodEnum;
use Illuminate\Support\Carbon;

readonly class FindMcpTraceMetricsParameters
{
    /**
     * @param TraceMetricFieldEnum[] $metrics
     * @param int[]                  $serviceIds
     * @param string[]               $types
     * @param string[]               $tags
     * @param string[]               $statuses
     */
    public function __construct(
        public TraceTimestampPeriodEnum $period,
        public ?TraceTimestampEnum $step,
        public array $metrics,
        public ?Carbon $to,
        public array $serviceIds = [],
        public array $types = [],
        public array $tags = [],
        public array $statuses = [],
        public ?float $durationFrom = null,
        public ?float $durationTo = null,
        public ?float $memoryFrom = null,
        public ?float $memoryTo = null,
        public ?float $cpuFrom = null,
        public ?float $cpuTo = null
    ) {
    }
}
