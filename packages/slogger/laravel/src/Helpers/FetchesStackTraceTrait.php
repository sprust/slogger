<?php

namespace SLoggerLaravel\Helpers;

use Illuminate\Support\Str;

trait FetchesStackTraceTrait
{
    protected function getCallerFromStackTrace(): array
    {
        $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))->forget(0);

        return $trace->first(
            function ($frame) {
                if (!isset($frame['file'])) {
                    return false;
                }

                return !Str::contains($frame['file'], base_path('vendor' . DIRECTORY_SEPARATOR));
            }
        ) ?? [];
    }
}
