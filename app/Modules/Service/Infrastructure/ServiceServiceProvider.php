<?php

namespace App\Modules\Service\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Service\Contracts\Actions\CreateServiceActionInterface;
use App\Modules\Service\Contracts\Actions\FindServiceByTokenActionInterface;
use App\Modules\Service\Contracts\Actions\FindServicesActionInterface;
use App\Modules\Service\Contracts\Repositories\ServiceRepositoryInterface;
use App\Modules\Service\Domain\Actions\CreateServiceAction;
use App\Modules\Service\Domain\Actions\FindServiceByTokenAction;
use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Infrastructure\Commands\CreateServiceCommand;
use App\Modules\Service\Infrastructure\Services\ServiceContainer;
use App\Modules\Service\Repositories\ServiceRepository;

class ServiceServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->app->singleton(ServiceContainer::class);

        $this->commands([
            CreateServiceCommand::class,
        ]);
    }

    protected function getContracts(): array
    {
        return [
            // repositories
            ServiceRepositoryInterface::class        => ServiceRepository::class,
            // actions
            CreateServiceActionInterface::class      => CreateServiceAction::class,
            FindServiceByTokenActionInterface::class => FindServiceByTokenAction::class,
            FindServicesActionInterface::class       => FindServicesAction::class,
        ];
    }
}
