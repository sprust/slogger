<?php

namespace RoadRunner;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RoadRunner\Servers\Jobs\RrQueueConnector;

class RrServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function register(): void
    {
        $this->booting(function () {
            $events = array_merge(
                config('roadrunner.http.listen') ?? [],
                config('roadrunner.jobs.listen') ?? [],
            );

            foreach ($events as $event => $listeners) {
                foreach (array_unique($listeners, SORT_REGULAR) as $listener) {
                    Event::listen($event, $listener);
                }
            }

            $this->app['queue']->addConnector('roadrunner', function (): RrQueueConnector {
                return new RrQueueConnector();
            });
        });
    }

    public function boot(): void
    {
        $this->publishes(
            paths: [
                __DIR__ . '/../config/roadrunner.php' => config_path('roadrunner.php'),
            ],
            groups: [
                'ifksco-roadrunner-laravel',
            ]
        );
    }
}
