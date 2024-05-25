<?php

namespace App\Modules\TraceCollector\Framework;

use App\Modules\TraceCollector\Adapters\Service\ServiceAdapter;
use App\Modules\TraceCollector\Framework\Commands\FreshTraceTimestampsCommand;
use App\Modules\TraceCollector\Framework\Commands\FreshTraceTreesCommand;
use App\Modules\TraceCollector\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\TraceCollector\Repositories\Interfaces\TraceTreeRepositoryInterface;
use App\Modules\TraceCollector\Repositories\TraceRepository;
use App\Modules\TraceCollector\Repositories\TraceTreeRepository;
use Illuminate\Support\ServiceProvider;

class TraceCollectorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(ServiceAdapter::class);

        $this->registerRepository();

        $this->commands([
            FreshTraceTreesCommand::class,
            FreshTraceTimestampsCommand::class,
        ]);
    }

    private function registerRepository(): void
    {
        $this->app->singleton(TraceRepositoryInterface::class, TraceRepository::class);
        $this->app->singleton(TraceTreeRepositoryInterface::class, TraceTreeRepository::class);
    }
}
