<?php

namespace App\Modules\TracesAggregator;

use App\Modules\TracesAggregator\Children\Repository\TraceChildrenRepository;
use App\Modules\TracesAggregator\Children\Repository\TraceChildrenRepositoryInterface;
use App\Modules\TracesAggregator\Parents\Repository\TraceParentsRepository;
use App\Modules\TracesAggregator\Parents\Repository\TraceParentsRepositoryInterface;
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
