<?php

namespace SLoggerLaravel;

use Illuminate\Support\ServiceProvider;
use SLoggerLaravel\Dispatcher\TraceDispatcherInterface;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;

class SLoggerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(SLoggerProcessor::class);
        $this->app->singleton(SLoggerTraceIdContainer::class);
        $this->app->singleton(
            TraceDispatcherInterface::class,
            $this->app['config']['slogger.dispatcher']
        );

        $this->registerWatchers();

        $this->publishes(
            paths: [
                __DIR__ . '/../config/slogger.php' => config_path('slogger.php'),
            ],
            groups: [
                'slogger-laravel',
            ]
        );
    }

    private function registerWatchers(): void
    {
        /** @var array[] $watcherConfigs */
        $watcherConfigs = $this->app['config']['slogger.watchers'];

        foreach ($watcherConfigs as $watcherConfig) {
            if (!$watcherConfig['enabled']) {
                continue;
            }

            /** @var AbstractSLoggerWatcher $watcher */
            $watcher = $this->app->make($watcherConfig['class']);

            $watcher->register();
        }
    }
}
