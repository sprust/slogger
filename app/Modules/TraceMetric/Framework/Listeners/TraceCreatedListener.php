<?php

namespace App\Modules\TraceMetric\Framework\Listeners;

use App\Modules\TraceCollector\Events\TraceCreatedEvent;
use App\Modules\TraceMetric\Domain\Actions\AddMetricAction;
use App\Modules\TraceMetric\Domain\Entities\Parameters\AddMetricParameters;

readonly class TraceCreatedListener
{
    public function __construct(private AddMetricAction $addMetricAction)
    {
    }

    public function handle(TraceCreatedEvent $event): void
    {
        $this->addMetricAction->handle(
            new AddMetricParameters(
                serviceId: $event->serviceId,
                type: $event->type,
                status: $event->status
            )
        );
    }
}
