<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Services;

use Illuminate\Support\Facades\Cache;

class MonitorTraceBufferHandlingService
{
    private string $cacheKey = 'trace-buffer-handling-monitor-start-timestamp';

    public function flush(): void
    {
        Cache::delete($this->cacheKey);
    }

    public function startProcess(): string
    {
        $processKey = now()->toDateTimeString('microsecond');

        Cache::forever($this->cacheKey, $processKey);

        return $processKey;
    }

    public function isProcessKeyActual(string $processKey): bool
    {
        return Cache::get($this->cacheKey) === $processKey;
    }
}
