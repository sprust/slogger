<?php

namespace App\Modules;

use App\Modules\Services\ServicesServiceProvider;
use App\Modules\Traces\TracesServiceProvider;

class ModulesConfig
{
    public static function providers(): array
    {
        return [
            ServicesServiceProvider::class,
            TracesServiceProvider::class,
        ];
    }
}
