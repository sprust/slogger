<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Timestamp;

use App\Modules\Trace\Enums\TraceTimestampPeriodEnum;

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
