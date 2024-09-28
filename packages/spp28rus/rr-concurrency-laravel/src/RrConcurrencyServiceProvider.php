<?php

namespace RrConcurrency;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RrConcurrency\Commands\JobsMonitorCommand;
use RrConcurrency\Services\Handlers\ConcurrencyRoadrunnerPusher;
use RrConcurrency\Services\Handlers\ConcurrencyPusherInterface;
use RrConcurrency\Services\JobsWaiter;
use RrConcurrency\Services\Roadrunner\RpcFactory;

class RrConcurrencyServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function register(): void
    {
        $this->commands([
            JobsMonitorCommand::class,
        ]);

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
        $this->app->singleton(RpcFactory::class, static function () {
            return new RpcFactory(
                host: config('rr-concurrency.rpc.host'),
                port: config('rr-concurrency.rpc.port')
            );
        });
        $this->app->singleton(JobsWaiter::class, static function (Application $app) {
            return new JobsWaiter(
                rpcFactory: $app->make(RpcFactory::class),
                storageName: config('rr-concurrency.kv.storage-name')
            );
        });
        $this->app->singleton(
            ConcurrencyPusherInterface::class,
            ConcurrencyRoadrunnerPusher::class
        );

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
