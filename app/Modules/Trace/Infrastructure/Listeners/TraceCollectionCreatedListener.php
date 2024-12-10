<?php

namespace App\Modules\Trace\Infrastructure\Listeners;

use App\Modules\Trace\Infrastructure\Jobs\FreshTraceTreesJob;
use App\Modules\Trace\Repositories\Events\TraceCollectionCreatedEvent;

class TraceCollectionCreatedListener
{
    public function handle(TraceCollectionCreatedEvent $event): void
    {
        dispatch(new FreshTraceTreesJob());
    }
}
