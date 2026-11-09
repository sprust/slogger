<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Infrastructure\Exceptions\AuthExceptionManager;
use App\Modules\Auth\Domain\Actions\FindUserByTokenAction;
use App\Modules\Auth\Domain\Actions\LoginAction;
use App\Modules\Common\Infrastructure\Providers\BaseServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class AuthServiceProvider extends BaseServiceProvider
{
    public static function setSchedule(Schedule $schedule): void
    {
    }

    protected function getContracts(): array
    {
        return [
            FindUserByTokenAction::class,
            LoginAction::class,
        ];
    }

    protected function getExceptionManagerClass(): ?string
    {
        return AuthExceptionManager::class;
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
}
