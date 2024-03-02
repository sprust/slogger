<?php

namespace App\Modules\Service;

use App\Modules\Service\Commands\CreateServiceCommand;
use App\Modules\Service\Http\ServiceContainer;
use App\Modules\Service\Repository\ServiceRepository;
use App\Modules\Service\Repository\ServiceRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(ServiceRepositoryInterface::class, ServiceRepository::class);
        $this->app->singleton(ServiceContainer::class);

        $this->commands([
            CreateServiceCommand::class,
        ]);
    }
}
