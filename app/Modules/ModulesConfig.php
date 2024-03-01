<?php

namespace App\Modules;

use App\Modules\Auth\AuthServiceProvider;
use App\Modules\Services\ServicesServiceProvider;
use App\Modules\TracesCollector\TracesServiceProvider;
use App\Modules\TracesAggregator\TracesAggregatorProvider;

class ModulesConfig
{
    public static function getProviders(): array
    {
        return [
            ServicesServiceProvider::class,
            TracesServiceProvider::class,
            TracesAggregatorProvider::class,
            AuthServiceProvider::class,
        ];
    }
}
