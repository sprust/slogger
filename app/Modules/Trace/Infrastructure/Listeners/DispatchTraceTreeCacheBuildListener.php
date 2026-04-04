<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Listeners;

use App\Modules\Trace\Domain\Events\TraceTreeCacheBuildRequestedEvent;
use App\Modules\Trace\Infrastructure\Jobs\BuildTraceTreeCacheJob;

class DispatchTraceTreeCacheBuildListener
{
    public function handle(TraceTreeCacheBuildRequestedEvent $event): void
    {
        dispatch(
            new BuildTraceTreeCacheJob(
                rootTraceId: $event->rootTraceId,
                version: $event->version,
            )
        );
    }
}
