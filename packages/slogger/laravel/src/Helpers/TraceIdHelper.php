<?php

namespace SLoggerLaravel\Helpers;

use Illuminate\Support\Str;

class TraceIdHelper
{
    public static function make(): string
    {
        return Str::slug(config('app.name')) . '-' . Str::uuid()->toString();
    }
}
