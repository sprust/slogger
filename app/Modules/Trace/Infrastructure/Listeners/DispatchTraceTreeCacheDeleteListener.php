<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Listeners;

use App\Modules\Trace\Domain\Events\TraceTreeCacheDeleteRequestedEvent;
use App\Modules\Trace\Infrastructure\Jobs\DeleteTraceTreeCacheJob;

class DispatchTraceTreeCacheDeleteListener
{
    public function handle(TraceTreeCacheDeleteRequestedEvent $event): void
    {
        dispatch(
            new DeleteTraceTreeCacheJob(
                rootTraceId: $event->rootTraceId,
                buildVersion: $event->buildVersion,
            )
        );
    }
}
