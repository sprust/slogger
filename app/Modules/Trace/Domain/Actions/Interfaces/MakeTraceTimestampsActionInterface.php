<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces;

use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampMetricObject;
use Illuminate\Support\Carbon;

interface MakeTraceTimestampsActionInterface
{
    /**
     * @return TraceTimestampMetricObject[]
     */
    public function handle(Carbon $date): array;
}
