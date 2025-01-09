<?php

namespace SLoggerLaravel\Watchers;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use SLoggerLaravel\Events\WatcherErrorEvent;
use SLoggerLaravel\Config;
use SLoggerLaravel\Processor;
use SLoggerLaravel\Traces\TraceIdContainer;
use Throwable;

abstract class AbstractWatcher
{
    private Dispatcher $events;

    abstract public function register(): void;

    public function __construct(
        protected readonly Application $app,
        protected readonly Processor $processor,
        protected readonly TraceIdContainer $traceIdContainer,
        protected readonly Config $loggerConfig,
    ) {
        $this->events = $this->app['events'];

        $this->init();
    }

    protected function init(): void
    {
    }

    protected function listenEvent(string $eventClass, array $function): void
    {
        $this->events->listen($eventClass, $function);
    }

    protected function safeHandleWatching(Closure $callback): mixed
    {
        if ($this->processor->isPaused()) {
            return null;
        }

        try {
            return $callback();
        } catch (Throwable $exception) {
            $this->processor->handleWithoutTracing(function () use ($exception) {
                $this->app['events']->dispatch(new WatcherErrorEvent($exception));
            });
        }

        return null;
    }
}
