<?php

namespace SLoggerLaravel\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Carbon;
use SLoggerLaravel\Dispatcher\SLoggerTraceDispatcherInterface;
use SLoggerLaravel\Dispatcher\SLoggerTracePushDispatcherParameters;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\SLoggerTraceHelper;
use SLoggerLaravel\SLoggerProcessor;
use SLoggerLaravel\Traces\SLoggerTraceIdContainer;

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

    protected function dispatchTrace(
        SLoggerTraceTypeEnum $type,
        array $tags,
        array $data,
        Carbon $loggedAt
    ): void {
        $this->traceDispatcher->push(
            new SLoggerTracePushDispatcherParameters(
                traceId: SLoggerTraceHelper::make(),
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
