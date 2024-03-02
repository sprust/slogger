<?php

namespace App\Modules;

use App\Modules\Auth\AuthServiceProvider;
use App\Modules\Services\ServicesServiceProvider;
use App\Modules\TraceCollector\TraceCollectorServiceProvider;
use App\Modules\TraceAggregator\TraceAggregatorProvider;

class ModulesConfig
{
    public static function getProviders(): array
    {
        return [
            ServicesServiceProvider::class,
            TraceCollectorServiceProvider::class,
            TraceAggregatorProvider::class,
            AuthServiceProvider::class,
        ];
    }
}
