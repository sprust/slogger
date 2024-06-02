<?php

namespace App\Modules\Trace\Framework;

use App\Modules\Trace\Repositories\Interfaces\TraceContentRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceTimestampsRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceTreeRepositoryInterface;
use App\Modules\Trace\Repositories\Services\TraceQueryBuilder;
use App\Modules\Trace\Repositories\TraceContentRepository;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceTimestampsRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;
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
        $this->app->singleton(TraceTimestampsRepositoryInterface::class, TraceTimestampsRepository::class);
    }
}
