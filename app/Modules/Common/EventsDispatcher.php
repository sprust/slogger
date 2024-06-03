<?php

namespace App\Modules\Common;

use Illuminate\Contracts\Foundation\Application;

readonly class EventsDispatcher
{
    public function __construct(private Application $app)
    {
    }

    public function dispatch(object $event): void
    {
        $this->app['events']->dispatch($event);
    }
}
