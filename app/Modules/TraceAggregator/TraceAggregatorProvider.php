<?php

namespace App\Modules\TraceAggregator;

use App\Modules\TraceAggregator\Adapters\AuthAdapter;
use App\Modules\TraceAggregator\Framework\Commands\FreshTraceTreesCommand;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceContentRepositoryInterface;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceTreeRepositoryInterface;
use App\Modules\TraceAggregator\Repositories\Services\TraceQueryBuilder;
use App\Modules\TraceAggregator\Repositories\TraceContentRepository;
use App\Modules\TraceAggregator\Repositories\TraceRepository;
use App\Modules\TraceAggregator\Repositories\TraceTreeRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TraceAggregatorProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(TraceQueryBuilder::class);

        $this->registerRepositories();
        $this->registerRoutes();

        $this->commands([
            FreshTraceTreesCommand::class,
        ]);
    }

    private function registerRepositories(): void
    {
        $this->app->singleton(TraceRepositoryInterface::class, TraceRepository::class);
        $this->app->singleton(TraceContentRepositoryInterface::class, TraceContentRepository::class);
        $this->app->singleton(TraceTreeRepositoryInterface::class, TraceTreeRepository::class);
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
                Route::prefix('/trace-aggregator')
                    ->as('trace-aggregator.')
                    ->group(function () {
                        include 'Framework/Http/Routes/routes.php';
                    });
            });
    }
}
