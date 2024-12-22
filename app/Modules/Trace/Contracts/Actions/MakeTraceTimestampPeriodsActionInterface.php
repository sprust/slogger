<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions;

use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampPeriodObject;

interface MakeTraceTimestampPeriodsActionInterface
{
    /**
     * @return TraceTimestampPeriodObject[]
     */
    public function handle(): array;
}
