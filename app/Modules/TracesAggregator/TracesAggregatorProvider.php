<?php

namespace App\Modules\TracesAggregator;

use App\Modules\TracesAggregator\Repository\TracesAggregatorRepository;
use App\Modules\TracesAggregator\Repository\TracesAggregatorRepositoryInterface;
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
        $this->app->singleton(TracesAggregatorRepositoryInterface::class, TracesAggregatorRepository::class);
    }

    private function registerRoutes(): void
    {
        // TODO
    }
}
