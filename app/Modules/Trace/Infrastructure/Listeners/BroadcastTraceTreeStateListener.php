<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Listeners;

use App\Modules\Trace\Domain\Events\TraceTreeCacheStateChangedEvent;
use App\Modules\Trace\Infrastructure\Broadcasting\TraceTreeStateBroadcast;
use App\Modules\Common\Domain\Services\DispatcherInterface;

/**
 * Turns the domain fact into the frame the panel receives.
 *
 * Dispatched rather than broadcast(): the dispatcher broadcasts a ShouldBroadcast event
 * by itself, and taking it as a dependency is what lets this be tested without a
 * container.
 */
readonly class BroadcastTraceTreeStateListener
{
    public function __construct(private DispatcherInterface $dispatcher)
    {
    }

    public function handle(TraceTreeCacheStateChangedEvent $event): void
    {
        $this->dispatcher->event(new TraceTreeStateBroadcast($event->state));
    }
}
