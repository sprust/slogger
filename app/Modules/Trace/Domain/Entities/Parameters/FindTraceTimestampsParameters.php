<?php

namespace App\Modules\Trace\Domain\Entities\Parameters;

use App\Modules\Trace\Domain\Entities\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Enums\TraceTimestampPeriodEnum;
use Illuminate\Support\Carbon;

readonly class FindTraceTimestampsParameters
{
    /**
     * @param TraceMetricFieldEnum[] $fields
     * @param string[]|null          $serviceIds
     * @param string[]|null          $dataFields
     * @param string[]|null          $traceIds
     * @param string[]               $types
     * @param string[]               $tags
     * @param string[]               $statuses
     */
    public function __construct(
        public TraceTimestampPeriodEnum $timestampPeriod,
        public TraceTimestampEnum $timestampStep,
        public array $fields,
        public ?array $dataFields = null,
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
