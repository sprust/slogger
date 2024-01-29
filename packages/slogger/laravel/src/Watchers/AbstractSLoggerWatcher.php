<?php

namespace SLoggerLaravel\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use SLoggerLaravel\Dispatcher\SLoggerTraceDispatcherInterface;
use SLoggerLaravel\SLoggerProcessor;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;
use Throwable;

abstract class AbstractSLoggerWatcher
{
    abstract public function register(): void;

    public function __construct(
        protected readonly Application $app,
        protected readonly SLoggerTraceDispatcherInterface $traceDispatcher,
        protected readonly SLoggerProcessor $processor,
        protected readonly SLoggerTraceIdContainer $traceIdContainer
    ) {
    }

    protected function listenEvent(string $eventClass, array $function): void
    {
        $this->app['events']->listen($eventClass, $function);
    }

    protected function prepareException(Throwable $exception): array
    {
        return [
            'message'   => $exception->getMessage(),
            'exception' => get_class($exception),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'trace'     => array_map(
                fn(array $item) => Arr::only($item, ['file', 'line']),
                $exception->getTrace()
            ),
        ];
    }
}
