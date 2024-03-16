<?php

namespace App\Modules\Service;

use App\Modules\Service\Adapters\Auth\AuthAdapter;
use App\Modules\Service\Api\ServiceApi;
use App\Modules\Service\Commands\CreateServiceCommand;
use App\Modules\Service\Http\Controllers\ServiceController;
use App\Modules\Service\Http\ServiceContainer;
use App\Modules\Service\Repository\ServiceRepository;
use App\Modules\Service\Repository\ServiceRepositoryInterface;
use App\Modules\Service\Services\ServiceService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(ServiceRepositoryInterface::class, ServiceRepository::class);
        $this->app->singleton(ServiceService::class);
        $this->app->singleton(ServiceContainer::class);
        $this->app->singleton(ServiceApi::class);

        $this->commands([
            CreateServiceCommand::class,
        ]);

        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $authMiddleware = $this->app->make(AuthAdapter::class)
            ->getAuthMiddleware();

        Route::prefix('admin-api')
            ->as('admin-api.')
            ->middleware([
                $authMiddleware,
            ])
            ->group(function () {
                Route::prefix('services')
                    ->as('services.')
                    ->group(function () {
                        Route::get('', [ServiceController::class, 'index'])->name('index');
                    });
            });
    }

}
