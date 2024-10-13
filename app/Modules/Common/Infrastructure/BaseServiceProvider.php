<?php

namespace App\Modules\Common\Infrastructure;

use Illuminate\Support\ServiceProvider;

abstract class BaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ($this->getContracts() as $interface => $implementation) {
            $this->app->singleton($interface, $implementation);
        }
    }

    protected function getContracts(): array
    {
        return [];
    }
}
