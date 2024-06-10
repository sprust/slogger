<?php

namespace App\Modules\Service\Framework;

use App\Modules\Common\Framework\BaseServiceProvider;
use App\Modules\Service\Domain\Actions\CreateServiceAction;
use App\Modules\Service\Domain\Actions\FindServiceByTokenAction;
use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Domain\Actions\Interfaces\CreateServiceActionInterface;
use App\Modules\Service\Domain\Actions\Interfaces\FindServiceByTokenActionInterface;
use App\Modules\Service\Domain\Actions\Interfaces\FindServicesActionInterface;
use App\Modules\Service\Framework\Commands\CreateServiceCommand;
use App\Modules\Service\Framework\Services\ServiceContainer;
use App\Modules\Service\Repositories\ServiceRepository;
use App\Modules\Service\Repositories\ServiceRepositoryInterface;

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
