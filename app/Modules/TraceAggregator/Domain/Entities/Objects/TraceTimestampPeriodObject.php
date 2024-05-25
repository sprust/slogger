<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

use App\Modules\TraceAggregator\Enums\TraceTimestampPeriodEnum;

class TraceTimestampPeriodObject
{
    /**
     * @param TraceTimestampObject[] $timestamps
     */
    public function __construct(
        public TraceTimestampPeriodEnum $period,
        public array $timestamps
    ) {
    }
}
