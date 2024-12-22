<?php

declare(strict_types=1);

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

    /**
     * @return array<int|class-string<object>, class-string<object>>
     */
    protected function getContracts(): array
    {
        return [];
    }

    protected function listen(string $eventClass, string $listenerClass): void
    {
        $this->app['events']->listen($eventClass, $listenerClass);
    }
}
