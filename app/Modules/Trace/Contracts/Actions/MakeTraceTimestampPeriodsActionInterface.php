<?php

namespace App\Modules\Trace\Contracts\Actions;

use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampPeriodObject;

interface MakeTraceTimestampPeriodsActionInterface
{
    /**
     * @return TraceTimestampPeriodObject[]
     */
    public function handle(): array;
}
