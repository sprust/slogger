<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Listeners;

use App\Modules\Common\Domain\Services\DispatcherInterface;
use App\Modules\Trace\Domain\Events\TraceTreeCacheDeleteRequestedEvent;
use App\Modules\Trace\Infrastructure\Jobs\DeleteTraceTreeCacheJob;

class DispatchTraceTreeCacheDeleteListener
{
    public function __construct(private readonly DispatcherInterface $dispatcher)
    {
    }

    public function handle(TraceTreeCacheDeleteRequestedEvent $event): void
    {
        $this->dispatcher->dispatch(
            new DeleteTraceTreeCacheJob(
                rootTraceId: $event->rootTraceId,
                buildVersion: $event->buildVersion,
            )
        );
    }
}
