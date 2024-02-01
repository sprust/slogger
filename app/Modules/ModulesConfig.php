<?php

namespace App\Modules;

use App\Modules\Services\ServicesServiceProvider;
use App\Modules\Traces\TracesServiceProvider;
use App\Modules\TracesAggregator\TracesAggregatorProvider;

class ModulesConfig
{
    public static function getProviders(): array
    {
        return [
            ServicesServiceProvider::class,
            TracesServiceProvider::class,
            TracesAggregatorProvider::class,
        ];
    }
}
