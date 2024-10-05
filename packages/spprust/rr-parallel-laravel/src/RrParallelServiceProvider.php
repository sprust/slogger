<?php

namespace RrParallel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RrParallel\Commands\StartJobsMonitorCommand;
use RrParallel\Commands\StopJobsMonitorCommand;
use RrParallel\Services\ParallelPusherInterface;
use RrParallel\Services\Drivers\Roadrunner\ParallelRoadrunnerPusher;
use RrParallel\Services\Drivers\Roadrunner\JobsWaiter;
use RrParallel\Services\Drivers\Roadrunner\RpcFactory;
use RrParallel\Services\Drivers\Sync\ParallelSyncPusher;

class RrParallelServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function register(): void
    {
        $this->commands([
            StartJobsMonitorCommand::class,
            StopJobsMonitorCommand::class,
        ]);

        $this->booting(function () {
            $events = array_merge(
                config('rr-parallel.jobs.listeners') ?? [],
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
                host: config('rr-parallel.rpc.host'),
                port: config('rr-parallel.rpc.port')
            );
        });
        $this->app->singleton(JobsWaiter::class, static function (Application $app) {
            return new JobsWaiter(
                rpcFactory: $app->make(RpcFactory::class),
                storageName: config('rr-parallel.kv.storage-name')
            );
        });
        $this->app->singleton(
            ParallelPusherInterface::class,
            match (config('rr-parallel.driver')) {
                'rr' => ParallelRoadrunnerPusher::class,
                'sync' => ParallelSyncPusher::class,
            }
        );

        $this->publishes(
            paths: [
                __DIR__ . '/../config/rr-parallel.php' => config_path('rr-parallel.php'),
            ],
            groups: [
                'rr-parallel-laravel',
            ]
        );
    }
}
