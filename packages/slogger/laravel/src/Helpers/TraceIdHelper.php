<?php

namespace SLoggerLaravel\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TraceIdHelper
{
    public static function make(): string
    {
        return Str::slug(config('app.name')) . '-' . Str::uuid()->toString();
    }

    public static function calcDuration(Carbon $startedAt): float
    {
        return round(
            num: $startedAt->clone()->setTimezone('UTC')
                ->diffInMicroseconds(now()->clone()->setTimezone('UTC')) * 0.000001,
            precision: 6
        );
    }
}
