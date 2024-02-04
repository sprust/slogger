<?php

namespace App\Modules\TracesAggregator;

use App\Modules\TracesAggregator\Repositories\TraceChildrenRepository;
use App\Modules\TracesAggregator\Repositories\TraceChildrenRepositoryInterface;
use App\Modules\TracesAggregator\Repositories\TraceParentsRepository;
use App\Modules\TracesAggregator\Repositories\TraceParentsRepositoryInterface;
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
        // TODO
    }
}
