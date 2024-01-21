<?php

namespace RoadRunner\Helpers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Application;

/**
 * Copied from laravel octane package
 */
trait DispatchesEvents
{
    public function dispatchEvent(Application $app, object $event): void
    {
        if ($app->bound(Dispatcher::class)) {
            $app[Dispatcher::class]->dispatch($event);
        }
    }
}
