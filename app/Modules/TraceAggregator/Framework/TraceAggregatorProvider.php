<?php

namespace App\Modules\TraceAggregator\Framework;

use App\Modules\TraceAggregator\Repositories\Interfaces\TraceContentRepositoryInterface;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceTreeRepositoryInterface;
use App\Modules\TraceAggregator\Repositories\Services\TraceQueryBuilder;
use App\Modules\TraceAggregator\Repositories\TraceContentRepository;
use App\Modules\TraceAggregator\Repositories\TraceRepository;
use App\Modules\TraceAggregator\Repositories\TraceTreeRepository;
use Illuminate\Support\ServiceProvider;

class TraceAggregatorProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(TraceQueryBuilder::class);

        $this->registerRepositories();
    }

    private function registerRepositories(): void
    {
        $this->app->singleton(TraceRepositoryInterface::class, TraceRepository::class);
        $this->app->singleton(TraceContentRepositoryInterface::class, TraceContentRepository::class);
        $this->app->singleton(TraceTreeRepositoryInterface::class, TraceTreeRepository::class);
    }
}
