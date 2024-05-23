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
            m: $date->clone()->startOfMonth(),
            d: $date->clone()->startOfDay(),
            h12: $this->sliceHours($date, 12),
            h4: $this->sliceHours($date, 4),
            h: $this->sliceHours($date, 1),
            min30: $this->sliceMinutes($date, 30),
            min10: $this->sliceMinutes($date, 10),
            min5: $this->sliceMinutes($date, 5),
            min: $this->sliceMinutes($date, 1),
            s30: $this->sliceSeconds($date, 30),
            s10: $this->sliceSeconds($date, 10),
            s5: $this->sliceSeconds($date, 5)
        );
    }

    public function sliceHours(Carbon $date, int $slice): Carbon
    {
        return $date->clone()
            ->setHours(
                $this->sliceValue($date->hour, $slice)
            )
            ->startOfHour();
    }

    public function sliceMinutes(Carbon $date, int $slice): Carbon
    {
        return $date->clone()
            ->setMinutes(
                $this->sliceValue($date->minute, $slice)
            )
            ->startOfMinute();
    }

    public function sliceSeconds(Carbon $date, int $slice): Carbon
    {
        return $date->clone()
            ->setSeconds(
                $this->sliceValue($date->second, $slice)
            );
    }

    private function sliceValue(int $value, int $slice): int
    {
        return $value - ($value % $slice);
    }
}
