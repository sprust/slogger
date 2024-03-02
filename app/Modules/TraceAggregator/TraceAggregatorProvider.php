<?php

namespace App\Modules\TraceAggregator;

use App\Modules\TraceAggregator\Commands\RefreshTraceTreesCommand;
use App\Modules\TraceAggregator\Http\TraceRoutes;
use App\Modules\TraceAggregator\Repositories\TraceRepository;
use App\Modules\TraceAggregator\Repositories\TraceRepositoryInterface;
use App\Modules\TraceAggregator\Repositories\TraceTreeRepository;
use App\Modules\TraceAggregator\Repositories\TraceTreeRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class TraceAggregatorProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRepositories();
        $this->registerRoutes();
        $this->commands([
            RefreshTraceTreesCommand::class,
        ]);
    }

    private function registerRepositories(): void
    {
        $this->app->singleton(TraceRepositoryInterface::class, TraceRepository::class);
        $this->app->singleton(TraceTreeRepositoryInterface::class, TraceTreeRepository::class);
    }

    private function registerRoutes(): void
    {
        $this->app->make(TraceRoutes::class)->init();
    }
}
