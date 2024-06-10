<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces;

use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampPeriodObject;

interface MakeTraceTimestampPeriodsActionInterface
{
    /**
     * @return TraceTimestampPeriodObject[]
     */
    public function handle(): array;
}
