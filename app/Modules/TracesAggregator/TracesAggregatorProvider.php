<?php

namespace App\Modules\TracesAggregator;

use App\Modules\TracesAggregator\Parents\Repository\TraceParentsRepository;
use App\Modules\TracesAggregator\Parents\Repository\TraceParentsRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class TracesAggregatorProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRepository();
        $this->registerRoutes();
    }

    private function registerRepository(): void
    {
        $this->app->singleton(TraceParentsRepositoryInterface::class, TraceParentsRepository::class);
    }

    private function registerRoutes(): void
    {
        // TODO
    }
}
