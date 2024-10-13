<?php

namespace RrMonitor;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RrMonitor\Commands\StartJobsMonitorCommand;
use RrMonitor\Commands\StopJobsMonitorCommand;
use RrMonitor\Services\JobsMonitor;
use Spiral\Goridge\RPC\RPC;

class RrMonitorServiceProvider extends ServiceProvider
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

        $this->app->singleton(JobsMonitor::class, static function (Application $app) {
            return new JobsMonitor(
                rpc: RPC::create(
                    sprintf(
                        'tcp://%s:%s',
                        config('rr-monitor.rpc.host'),
                        config('rr-monitor.rpc.port'),
                    )
                ),
                app: $app
            );
        });

        $this->booting(function () {
            $events = array_merge(
                config('rr-monitor.jobs.listeners') ?? [],
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
        $this->publishes(
            paths: [
                __DIR__ . '/../config/rr-monitor.php' => config_path('rr-monitor.php'),
            ],
            groups: [
                'rr-monitor-laravel',
            ]
        );
    }
}
