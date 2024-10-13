<?php

namespace App\Modules;

use App\Modules\Auth\Infrastructure\AuthServiceProvider;
use App\Modules\Cleaner\Infrastructure\CleanerServiceProvider;
use App\Modules\Dashboard\Infrastructure\DashboardProvider;
use App\Modules\Service\Infrastructure\ServiceServiceProvider;
use App\Modules\Trace\Framework\TraceServiceProvider;
use App\Modules\User\Infrastructure\UserServiceProvider;

class ModulesConfig
{
    public static function getProviders(): array
    {
        return [
            ServiceServiceProvider::class,
            TraceServiceProvider::class,
            AuthServiceProvider::class,
            UserServiceProvider::class,
            DashboardProvider::class,
            CleanerServiceProvider::class,
        ];
    }
}
