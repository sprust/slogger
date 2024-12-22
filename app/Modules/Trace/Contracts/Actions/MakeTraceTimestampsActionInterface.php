<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions;

use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampMetricObject;
use Illuminate\Support\Carbon;

interface MakeTraceTimestampsActionInterface
{
    /**
     * @return TraceTimestampMetricObject[]
     */
    public function handle(Carbon $date): array;
}
