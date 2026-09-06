<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Listeners;

use App\Modules\Trace\Domain\Events\TraceDynamicIndexBuiltEvent;
use App\Modules\Trace\Infrastructure\Broadcasting\TraceDynamicIndexBuiltBroadcast;
use Illuminate\Contracts\Events\Dispatcher;

readonly class BroadcastTraceDynamicIndexBuiltListener
{
    public function __construct(
        private Dispatcher $events,
    ) {
    }

    public function handle(TraceDynamicIndexBuiltEvent $event): void
    {
        $this->events->dispatch(new TraceDynamicIndexBuiltBroadcast($event));
    }
}
