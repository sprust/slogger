<?php

namespace App\Console\Commands\Cron;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

abstract class BaseCronCommand extends Command
{
    private string $cacheRestartFlagKey = 'cron-restart-flag';

    protected function setRestartFlag(bool $restartFlag): void
    {
        Cache::set($this->cacheRestartFlagKey, $restartFlag);
    }

    protected function hasRestartFlag(): bool
    {
        return (bool) Cache::get($this->cacheRestartFlagKey);
    }
}
