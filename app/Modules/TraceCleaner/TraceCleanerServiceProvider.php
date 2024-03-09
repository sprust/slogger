<?php

namespace App\Modules\TraceCleaner;

use App\Modules\TraceCleaner\Adapters\AuthAdapter;
use App\Modules\TraceCleaner\Commands\ClearTracesCommand;
use App\Modules\TraceCleaner\Http\Controllers\SettingController;
use App\Modules\TraceCleaner\Repositories\Contracts\ProcessRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Contracts\SettingRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Contracts\TraceRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Contracts\TraceTreeRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\ProcessRepository;
use App\Modules\TraceCleaner\Repositories\SettingRepository;
use App\Modules\TraceCleaner\Repositories\TraceRepository;
use App\Modules\TraceCleaner\Repositories\TraceTreeRepository;
use App\Modules\TraceCleaner\Services\CleaningService;
use App\Modules\TraceCleaner\Services\SettingService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TraceCleanerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRepositories();

        $this->app->singleton(CleaningService::class);
        $this->app->singleton(SettingService::class);

        $this->commands([
            ClearTracesCommand::class,
        ]);

        $this->registerRoutes();
    }

    private function registerRepositories(): void
    {
        $this->app->singleton(
            ProcessRepositoryInterface::class,
            ProcessRepository::class
        );
        $this->app->singleton(
            SettingRepositoryInterface::class,
            SettingRepository::class
        );
        $this->app->singleton(
            TraceRepositoryInterface::class,
            TraceRepository::class
        );
        $this->app->singleton(
            TraceTreeRepositoryInterface::class,
            TraceTreeRepository::class
        );
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
                Route::prefix('/trace-cleaner')
                    ->as('trace-cleaner.')
                    ->group(function () {
                        Route::prefix('/settings')
                            ->as('settings.')
                            ->group(function () {
                                Route::get('/', [SettingController::class, 'index'])
                                    ->name('index');
                                Route::post('/', [SettingController::class, 'storeOrUpdate'])
                                    ->name('store-or-update');
                                Route::delete('/{settingId}', [SettingController::class, 'destroy'])
                                    ->name('destroy');
                            });
                    });
            });
    }
}
