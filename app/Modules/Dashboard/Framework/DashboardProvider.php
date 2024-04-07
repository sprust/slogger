<?php

namespace App\Modules\Dashboard\Framework;

use App\Modules\Dashboard\Adapters\AuthAdapter;
use App\Modules\Dashboard\Adapters\Service\ServiceAdapter;
use App\Modules\Dashboard\Domain\Actions\FindServiceStatAction;
use App\Modules\Dashboard\Framework\Http\Controllers\DatabaseStatController;
use App\Modules\Dashboard\Framework\Http\Controllers\ServiceStatController;
use App\Modules\Dashboard\Repositories\DatabaseStatRepository;
use App\Modules\Dashboard\Repositories\Interfaces\DatabaseStatRepositoryInterface;
use App\Modules\Dashboard\Repositories\Interfaces\ServiceStatRepositoryInterface;
use App\Modules\Dashboard\Repositories\ServiceStatRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DashboardProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(DatabaseStatRepositoryInterface::class, DatabaseStatRepository::class);
        $this->app->singleton(ServiceStatRepositoryInterface::class, ServiceStatRepository::class);

        $this->app->singleton(ServiceAdapter::class);
        $this->app->singleton(FindServiceStatAction::class);

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
                Route::prefix('/dashboard')
                    ->as('dashboard.')
                    ->group(function () {
                        Route::get('/database', [DatabaseStatController::class, 'index'])->name('index');
                        Route::get('/service-stat', [ServiceStatController::class, 'index'])->name('index');
                    });
            });
    }
}
