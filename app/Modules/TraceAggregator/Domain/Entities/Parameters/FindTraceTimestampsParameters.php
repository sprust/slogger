<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Parameters;

use App\Modules\TraceAggregator\Domain\Entities\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TraceAggregator\Enums\TimestampPeriodEnum;
use Illuminate\Support\Carbon;

readonly class FindTraceTimestampsParameters
{
    public function __construct(
        public TimestampPeriodEnum $timestampPeriod,
        public ?array $serviceIds = null,
        public ?array $traceIds = null,
        public ?Carbon $loggedAtTo = null,
        public array $types = [],
        public array $tags = [],
        public array $statuses = [],
        public ?float $durationFrom = null,
        public ?float $durationTo = null,
        public ?TraceDataFilterParameters $data = null,
        public ?bool $hasProfiling = null,
    ) {
    }
}
