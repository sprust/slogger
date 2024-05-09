<?php

namespace App\Modules\Common\Events;

use Illuminate\Contracts\Events\Dispatcher;

readonly class EventsDispatcher
{
    public function __construct(private Dispatcher $dispatcher)
    {
    }

    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
