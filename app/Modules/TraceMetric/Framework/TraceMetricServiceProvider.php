<?php

namespace App\Modules\TraceMetric\Framework;

use App\Modules\TraceCollector\Events\TraceCreatedEvent;
use App\Modules\TraceMetric\Domain\Actions\AddMetricAction;
use App\Modules\TraceMetric\Framework\Listeners\TraceCreatedListener;
use App\Modules\TraceMetric\Repositories\TraceMetricRepository;
use App\Modules\TraceMetric\Repositories\TraceMetricRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class TraceMetricServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app['events']->listen(TraceCreatedEvent::class, TraceCreatedListener::class);

        $this->app->singleton(TraceMetricRepositoryInterface::class, TraceMetricRepository::class);

        $this->app->singleton(AddMetricAction::class);
    }
}
