<?php

namespace SLoggerLaravel\Helpers;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Throwable;

class SLoggerTraceHelper
{
    public static function make(): string
    {
        return Str::slug(config('app.name')) . '-' . Str::uuid()->toString();
    }

    public static function calcDuration(Carbon $startedAt): float
    {
        return self::roundDuration(
            $startedAt->clone()->setTimezone('UTC')
                ->diffInMicroseconds(now()->setTimezone('UTC')) * 0.000001
        );
    }

    public static function roundDuration(float $duration): float
    {
        return round($duration, 6);
    }

    public static function getCallerFromStackTrace(array $keys = [0]): array
    {
        $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))->forget($keys);

        return $trace->first(
            function ($frame) {
                if (!isset($frame['file'])) {
                    return false;
                }

                return !Str::contains($frame['file'], base_path('vendor' . DIRECTORY_SEPARATOR));
            }
        ) ?? [];
    }

    /**
     * @throws Throwable
     */
    public static function muteHandle(Closure $callback): mixed
    {
        AbstractSLoggerWatcher::toMute();

        $exception = null;

        try {
            $result = $callback();
        } catch (Throwable $exception) {
            $result = null;
        }

        AbstractSLoggerWatcher::unMute();

        if ($exception) {
            throw $exception;
        }

        return $result;
    }
}
