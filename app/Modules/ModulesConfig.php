<?php

declare(strict_types=1);

namespace App\Modules;

use App\Modules\Auth\Infrastructure\AuthServiceProvider;
use App\Modules\Cleaner\Infrastructure\CleanerServiceProvider;
use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Dashboard\Infrastructure\DashboardProvider;
use App\Modules\Logs\Infrastructure\LogsServiceProvider;
use App\Modules\Service\Infrastructure\ServiceServiceProvider;
use App\Modules\Trace\Infrastructure\TraceServiceProvider;
use App\Modules\User\Infrastructure\UserServiceProvider;

class ModulesConfig
{
    /**
     * @return array<class-string<BaseServiceProvider>>
     */
    public static function getProviders(): array
    {
        return [
            ServiceServiceProvider::class,
            TraceServiceProvider::class,
            AuthServiceProvider::class,
            UserServiceProvider::class,
            DashboardProvider::class,
            CleanerServiceProvider::class,
            LogsServiceProvider::class,
        ];
    }
}
