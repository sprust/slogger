<?php

namespace App\Modules\TracesAggregator;

use App\Modules\TracesAggregator\Adapters\TracesAggregatorAuthAdapter;
use App\Modules\TracesAggregator\Http\Controllers\TraceAggregatorParentsController;
use App\Modules\TracesAggregator\Repositories\TraceChildrenRepository;
use App\Modules\TracesAggregator\Repositories\TraceChildrenRepositoryInterface;
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
        $this->app->singleton(TraceChildrenRepositoryInterface::class, TraceChildrenRepository::class);
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
                        Route::get('/parents', [TraceAggregatorParentsController::class, 'index']);
                    });
            });
    }
}
