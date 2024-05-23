<?php

namespace App\Modules\TraceCollector\Domain\Actions;

use App\Modules\TraceCollector\Domain\Entities\Objects\TraceTimestampsObject;
use Illuminate\Support\Carbon;

class CreateTraceTimestampsAction
{
    public function handle(Carbon $date): TraceTimestampsObject
    {
        $date = $date->clone()->setMicroseconds(0);

        return new TraceTimestampsObject(
            y: $date->clone()->startOfYear(),
            m: $date->clone()->startOfMonth(),
            d: $date->clone()->startOfDay(),
            h: $date->clone()->startOfHour(),
            min: $date->clone()->startOfMinute(),
            s30: $date->clone()->setSeconds(
                $date->second - ($date->second % 30)
            ),
            s10: $date->clone()->setSeconds(
                $date->second - ($date->second % 10)
            ),
            s5: $date->clone()->setSeconds(
                $date->second - ($date->second % 5)
            )
        );
    }
}
