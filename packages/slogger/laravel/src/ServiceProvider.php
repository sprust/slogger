<?php

namespace SLoggerLaravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Queue;
use SLoggerLaravel\Dispatcher\Queue\ApiClients\ApiClientFactory;
use SLoggerLaravel\Dispatcher\Queue\ApiClients\ApiClientInterface;
use SLoggerLaravel\Dispatcher\DispatcherFactory;
use SLoggerLaravel\Dispatcher\TraceDispatcherInterface;
use SLoggerLaravel\Dispatcher\Transporter\Clients\TransporterClient;
use SLoggerLaravel\Dispatcher\Transporter\Clients\TransporterClientInterface;
use SLoggerLaravel\Middleware\HttpMiddleware;
use SLoggerLaravel\Profiling\AbstractProfiling;
use SLoggerLaravel\Profiling\XHProfProfiler;
use SLoggerLaravel\Traces\TraceIdContainer;
use SLoggerLaravel\Watchers\AbstractWatcher;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(Config::class);
        $this->app->singleton(State::class);
        $this->app->singleton(Processor::class);
        $this->app->singleton(TraceIdContainer::class);
        $this->app->singleton(HttpMiddleware::class);
        $this->app->singleton(AbstractProfiling::class, XHProfProfiler::class);

        $this->app->singleton(ApiClientInterface::class, static function (Application $app) {
            return $app->make(ApiClientFactory::class)->create(
                config('slogger.dispatchers.queue.api_clients.default')
            );
        });

        $this->app->singleton(TransporterClientInterface::class, static function () {
            return new TransporterClient(
                apiToken: config('slogger.token'),
                connection: Queue::connection(config('slogger.dispatchers.transporter.queue.connection')),
                queueName: config('slogger.dispatchers.transporter.queue.name')
            );
        });

        $this->app->singleton(TraceDispatcherInterface::class, static function (Application $app) {
            return $app->make(DispatcherFactory::class)->create(
                config('slogger.dispatchers.default')
            );
        });

        $this->registerListeners();

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

    private function registerListeners(): void
    {
        $events = $this->app['events'];

        foreach ($this->app['config']['slogger.listeners'] ?? [] as $eventClass => $listenerClasses) {
            foreach ($listenerClasses as $listenerClass) {
                $events->listen($eventClass, $listenerClass);
            }
        }
    }

    private function registerWatchers(): void
    {
        if (!$this->app['config']['slogger.enabled']) {
            return;
        }

        $state = $this->app->make(State::class);

        /** @var array[] $watcherConfigs */
        $watcherConfigs = $this->app['config']['slogger.watchers'] ?? [];

        foreach ($watcherConfigs as $watcherConfig) {
            if (!$watcherConfig['enabled']) {
                continue;
            }

            $watcherClass = $watcherConfig['class'];

            $state->addEnabledWatcher($watcherClass);

            /** @var AbstractWatcher $watcher */
            $watcher = $this->app->make($watcherClass);

            $watcher->register();
        }
    }
}
