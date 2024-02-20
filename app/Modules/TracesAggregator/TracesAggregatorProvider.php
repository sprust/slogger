<?php

namespace App\Modules\TracesAggregator;

use App\Modules\TracesAggregator\Adapters\TracesAggregatorAuthAdapter;
use App\Modules\TracesAggregator\Http\Controllers\TraceAggregatorParentsController;
use App\Modules\TracesAggregator\Http\Controllers\TraceAggregatorTreeController;
use App\Modules\TracesAggregator\Repositories\TraceTreeRepository;
use App\Modules\TracesAggregator\Repositories\TraceTreeRepositoryInterface;
use App\Modules\TracesAggregator\Repositories\TraceParentsRepository;
use App\Modules\TracesAggregator\Repositories\TraceParentsRepositoryInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TracesAggregatorProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRepositories();
        $this->registerRoutes();
    }

    private function registerRepositories(): void
    {
        $this->app->singleton(TraceParentsRepositoryInterface::class, TraceParentsRepository::class);
        $this->app->singleton(TraceTreeRepositoryInterface::class, TraceTreeRepository::class);
    }

    private function registerRoutes(): void
    {
        $authMiddleware = $this->app->make(TracesAggregatorAuthAdapter::class)
            ->getAuthMiddleware();

        Route::prefix('admin-api')
            ->as('admin-api.')
            ->middleware([
                $authMiddleware,
            ])
            ->group(function () {
                Route::prefix('/trace-aggregator')
                    ->as('trace-aggregator.')
                    ->group(function () {
                        Route::prefix('parents')
                            ->as('parents.')
                            ->group(function () {
                                Route::post(
                                    '',
                                    [TraceAggregatorParentsController::class, 'index']
                                )->name('index');
                                Route::post(
                                    'types',
                                    [TraceAggregatorParentsController::class, 'types']
                                )->name('types');
                                Route::post(
                                    'tags',
                                    [TraceAggregatorParentsController::class, 'tags']
                                )->name('tags');
                            });
                        Route::get(
                            '{traceId}',
                            [TraceAggregatorTreeController::class, 'tree']
                        )->name('tree');
                    });
            });
    }
}
