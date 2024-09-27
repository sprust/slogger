<?php

namespace RrConcurrency;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RrConcurrency\Services\ConcurrencyService;
use RrConcurrency\Services\ConcurrencyServiceInterface;
use RrConcurrency\Services\JobsWaiter;

class RrConcurrencyServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function register(): void
    {
        $this->booting(function () {
            $events = array_merge(
                config('rr-concurrency.jobs.listeners') ?? [],
            );

            foreach ($events as $event => $listeners) {
                foreach (array_unique($listeners, SORT_REGULAR) as $listener) {
                    Event::listen($event, $listener);
                }
            }
        });
    }

    public function boot(): void
    {
        $this->app->singleton(ConcurrencyServiceInterface::class, ConcurrencyService::class);
        $this->app->singleton(JobsWaiter::class);

        $this->publishes(
            paths: [
                __DIR__ . '/../config/rr-concurrency.php' => config_path('rr-concurrency.php'),
            ],
            groups: [
                'rr-concurrency-laravel',
            ]
        );
    }
}
