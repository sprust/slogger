<?php

namespace App\Modules\TracesAggregator;

use App\Modules\TracesAggregator\Commands\RefreshTraceTreesCommand;
use App\Modules\TracesAggregator\Http\TracesAggregatorRoutes;
use App\Modules\TracesAggregator\Repositories\TraceParentsRepository;
use App\Modules\TracesAggregator\Repositories\TraceParentsRepositoryInterface;
use App\Modules\TracesAggregator\Repositories\TraceTreeRepository;
use App\Modules\TracesAggregator\Repositories\TraceTreeRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class TracesAggregatorProvider extends ServiceProvider
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
        $this->app->singleton(TraceParentsRepositoryInterface::class, TraceParentsRepository::class);
        $this->app->singleton(TraceTreeRepositoryInterface::class, TraceTreeRepository::class);
    }

    private function registerRoutes(): void
    {
        $this->app->make(TracesAggregatorRoutes::class)->init();
    }
}
