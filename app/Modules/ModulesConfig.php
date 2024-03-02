<?php

namespace App\Modules;

use App\Modules\Auth\AuthServiceProvider;
use App\Modules\Services\ServicesServiceProvider;
use App\Modules\TracesCollector\TracesServiceProvider;
use App\Modules\TraceAggregator\TraceAggregatorProvider;

class ModulesConfig
{
    public static function getProviders(): array
    {
        return [
            ServicesServiceProvider::class,
            TracesServiceProvider::class,
            TraceAggregatorProvider::class,
            AuthServiceProvider::class,
        ];
    }
}
