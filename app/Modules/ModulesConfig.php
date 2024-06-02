<?php

namespace App\Modules;

use App\Modules\Auth\Framework\AuthServiceProvider;
use App\Modules\Dashboard\Framework\DashboardProvider;
use App\Modules\Service\Framework\ServiceServiceProvider;
use App\Modules\Trace\Framework\TraceProvider;
use App\Modules\Trace\Framework\TraceCleanerServiceProvider;
use App\Modules\User\Framework\UserServiceProvider;

class ModulesConfig
{
    public static function getProviders(): array
    {
        return [
            ServiceServiceProvider::class,
            TraceProvider::class,
            AuthServiceProvider::class,
            UserServiceProvider::class,
            DashboardProvider::class,
            TraceCleanerServiceProvider::class,
        ];
    }
}
