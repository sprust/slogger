<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure;

use App\Modules\Common\Infrastructure\Exceptions\CommonExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\ExceptionManagers;
use App\Modules\Common\Domain\Services\DispatcherInterface;
use App\Modules\Common\Infrastructure\Services\Dispatcher;
use App\Modules\Common\Domain\Services\Mutex\MutexManagerInterface;
use App\Modules\Common\Infrastructure\Services\MutexManager;
use App\Modules\Common\Infrastructure\Providers\BaseServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class CommonServiceProvider extends BaseServiceProvider
{
    public static function setSchedule(Schedule $schedule): void
    {
    }

    protected function getContracts(): array
    {
        return [
            ExceptionManagers::class,
            DispatcherInterface::class   => Dispatcher::class,
            MutexManagerInterface::class => MutexManager::class,
        ];
    }

    protected function getCommands(): array
    {
        return [];
    }

    protected function getJobs(): array
    {
        return [];
    }

    protected function getListeners(): array
    {
        return [];
    }

    protected function getExceptionManagerClass(): ?string
    {
        return CommonExceptionManager::class;
    }
}
