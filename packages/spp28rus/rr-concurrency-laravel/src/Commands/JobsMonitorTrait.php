<?php

namespace RrConcurrency\Commands;

use Illuminate\Support\Facades\Cache;

trait JobsMonitorTrait
{
    private string $cacheKeyStop = 'rr-concurrency-monitor-status';

    private function forgetStopSignal(string $pluginName): void
    {
        Cache::forget($this->makeStopSignalCacheKey($pluginName));
    }

    private function isTimeToStop(string $pluginName): bool
    {
        if (Cache::has($this->makeStopSignalCacheKey($pluginName))) {
            $this->forgetStopSignal($pluginName);

            return true;
        }

        return false;
    }

    private function setStopSignalToStop(string $pluginName): void
    {
        Cache::put($this->makeStopSignalCacheKey($pluginName), true);
    }

    private function makeStopSignalCacheKey(string $pluginName): string
    {
        return "$this->cacheKeyStop:$pluginName";
    }
}
