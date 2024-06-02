<?php

namespace App\Modules\Trace\Framework;

use App\Modules\Trace\Framework\Commands\FreshTraceTimestampsCommand;
use App\Modules\Trace\Framework\Commands\FreshTraceTreesCommand;
use App\Modules\Trace\Repositories\CollectorTraceRepository;
use App\Modules\Trace\Repositories\CollectorTraceTreeRepository;
use App\Modules\Trace\Repositories\Interfaces\CollectorTraceRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\CollectorTraceTreeRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class TraceCollectorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRepository();

        $this->commands([
            FreshTraceTreesCommand::class,
            FreshTraceTimestampsCommand::class,
        ]);
    }

    private function registerRepository(): void
    {
        $this->app->singleton(CollectorTraceRepositoryInterface::class, CollectorTraceRepository::class);
        $this->app->singleton(CollectorTraceTreeRepositoryInterface::class, CollectorTraceTreeRepository::class);
    }
}
