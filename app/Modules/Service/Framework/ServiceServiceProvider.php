<?php

namespace App\Modules\Service\Framework;

use App\Modules\Service\Adapters\Auth\AuthAdapter;
use App\Modules\Service\Domain\Actions\FindServiceByTokenAction;
use App\Modules\Service\Framework\Commands\CreateServiceCommand;
use App\Modules\Service\Framework\Http\Controllers\ServiceController;
use App\Modules\Service\Framework\Http\ServiceContainer;
use App\Modules\Service\Repositories\ServiceRepository;
use App\Modules\Service\Repositories\ServiceRepositoryInterface;
use Illuminate\Support\Facades\Route;
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
