<?php

namespace App\Modules\Dashboard;

use App\Modules\Dashboard\Adapters\AuthAdapter;
use App\Modules\Dashboard\Http\Controllers\DatabaseController;
use App\Modules\Dashboard\Repositories\DatabaseRepository;
use App\Modules\Dashboard\Repositories\DatabaseRepositoryInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DashboardProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(DatabaseRepositoryInterface::class, DatabaseRepository::class);

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
                        Route::get('/database', [DatabaseController::class, 'index'])->name('index');
                    });
            });
    }
}
