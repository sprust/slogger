<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Services;

use Illuminate\Support\Facades\Cache;

class MonitorTraceDynamicIndexesService
{
    private string $cacheKey  = 'trace-dynamic-indexes-monitor-stop-flag';

    public function setStopFlag(bool $restartFlag): void
    {
        Cache::set($this->cacheKey, $restartFlag);
    }

    public function hasRestartFlag(): bool
    {
        return (bool) Cache::get($this->cacheKey);
    }

}
