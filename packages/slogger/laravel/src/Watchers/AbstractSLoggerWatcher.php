<?php

namespace SLoggerLaravel\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Carbon;
use SLoggerLaravel\Dispatcher\TraceDispatcherInterface;
use SLoggerLaravel\Dispatcher\TracePushDispatcherParameters;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\TraceIdHelper;
use SLoggerLaravel\SLoggerProcessor;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;

abstract class AbstractSLoggerWatcher
{
    abstract public function register(): void;

    public function __construct(
        protected readonly Application $app,
        protected readonly TraceDispatcherInterface $traceDispatcher,
        protected readonly SLoggerProcessor $processor,
        protected readonly SLoggerTraceIdContainer $traceIdContainer
    ) {
    }

    protected function dispatchTrace(
        SLoggerTraceTypeEnum $type,
        array $tags,
        array $data,
        Carbon $loggedAt
    ): void {
        $this->traceDispatcher->push(
            new TracePushDispatcherParameters(
                traceId: TraceIdHelper::make(),
                parentTraceId: $this->traceIdContainer->getParentTraceId(),
                type: $type,
                tags: $tags,
                data: $data,
                loggedAt: $loggedAt->clone()->setTimezone('UTC')
            )
        );
    }

    protected function listenEvent(string $eventClass, array $function): void
    {
        $this->app['events']->listen($eventClass, $function);
    }
}
