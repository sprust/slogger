<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Listeners;

use App\Modules\Common\Domain\Services\DispatcherInterface;
use App\Modules\Trace\Domain\Events\TraceTreeCacheBuildRequestedEvent;
use App\Modules\Trace\Infrastructure\Jobs\BuildTraceTreeCacheJob;

class DispatchTraceTreeCacheBuildListener
{
    public function __construct(private readonly DispatcherInterface $dispatcher)
    {
    }

    public function handle(TraceTreeCacheBuildRequestedEvent $event): void
    {
        $this->dispatcher->dispatch(
            new BuildTraceTreeCacheJob(
                rootTraceId: $event->rootTraceId,
                version: $event->version,
                depth: $event->depth,
                afterId: $event->afterId,
            )
        );
    }
}
