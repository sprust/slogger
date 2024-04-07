<?php

namespace App\Modules\TraceCleaner\Framework;

use App\Modules\TraceCleaner\Adapters\AuthAdapter;
use App\Modules\TraceCleaner\Domain\Actions\ClearAction;
use App\Modules\TraceCleaner\Framework\Commands\ClearTracesCommand;
use App\Modules\TraceCleaner\Framework\Http\Controllers\ProcessController;
use App\Modules\TraceCleaner\Framework\Http\Controllers\SettingController;
use App\Modules\TraceCleaner\Repositories\Interfaces\ProcessRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Interfaces\SettingRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Interfaces\TraceTreeRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\ProcessRepository;
use App\Modules\TraceCleaner\Repositories\SettingRepository;
use App\Modules\TraceCleaner\Repositories\TraceRepository;
use App\Modules\TraceCleaner\Repositories\TraceTreeRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TraceCleanerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRepositories();

        $this->app->singleton(ClearAction::class);

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
                                Route::post('/', [SettingController::class, 'store'])
                                    ->name('store');
                                Route::patch('/{settingId}', [SettingController::class, 'update'])
                                    ->name('update');
                                Route::delete('/{settingId}', [SettingController::class, 'destroy'])
                                    ->name('destroy');
                                Route::get('/{settingId}/processes', [ProcessController::class, 'index'])
                                    ->name('processes');
                            });
                    });
            });
    }
}
