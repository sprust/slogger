<?php

namespace App\Modules\Service\Framework;

use App\Modules\Service\Domain\Actions\FindServiceByTokenAction;
use App\Modules\Service\Framework\Commands\CreateServiceCommand;
use App\Modules\Service\Framework\Services\ServiceContainer;
use App\Modules\Service\Repositories\ServiceRepository;
use App\Modules\Service\Repositories\ServiceRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(ServiceRepositoryInterface::class, ServiceRepository::class);
        $this->app->singleton(FindServiceByTokenAction::class);
        $this->app->singleton(ServiceContainer::class);

        $this->commands([
            CreateServiceCommand::class,
        ]);
    }
}
