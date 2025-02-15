<?php

namespace App\Console\Commands\Cron;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

abstract class BaseCronCommand extends Command
{
    private string $cacheKey = 'cron-session-key';

    protected function setSessionKey(): string
    {
        $sessionKey = now()->toDateTimeString('microsecond');

        Cache::set($this->cacheKey, $sessionKey);

        return $sessionKey;
    }

    protected function isSessionKeyActive(string $sessionKey): bool
    {
        return Cache::get($this->cacheKey) === $sessionKey;
    }

    protected function forgetSessionKey(): void
    {
        Cache::forget($this->cacheKey);
    }
}
