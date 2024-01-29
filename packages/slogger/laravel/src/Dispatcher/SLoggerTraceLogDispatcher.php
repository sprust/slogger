<?php

namespace SLoggerLaravel\Dispatcher;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;

/**
 * @example
 */
class SLoggerTraceLogDispatcher implements SLoggerTraceDispatcherInterface
{
    /** @var SLoggerTracePushDispatcherParameters[] */
    private array $traces = [];

    public function __construct(private readonly Application $app)
    {
    }

    public function push(SLoggerTracePushDispatcherParameters $parameters): void
    {
        $this->traces[] = $parameters;
    }

    public function stop(SLoggerTraceStopDispatcherParameters $parameters): void
    {
        if (!$this->traces) {
            return;
        }

        $filtered = array_filter(
            $this->traces,
            fn(SLoggerTracePushDispatcherParameters $traceItem) => $traceItem->parentTraceId === $parameters->traceId
                || $traceItem->traceId === $parameters->traceId
        );

        $traces = Arr::sort(
            $filtered,
            fn(SLoggerTracePushDispatcherParameters $traceItem) => $traceItem->loggedAt->getTimestampMs()
        );

        /** @var SLoggerTracePushDispatcherParameters $parentTrace */
        $parentTrace = Arr::first(
            $traces,
            fn(SLoggerTracePushDispatcherParameters $traceItem) => $traceItem->traceId === $parameters->traceId
        );

        if (!is_null($parameters->data)) {
            $parentTrace->data = $parameters->data;
        }


        if (!is_null($parameters->tags)) {
            $parentTrace->tags = $parameters->tags;
        }

        $storage = $this->app['filesystem']->build([
            'driver' => 'local',
            'root'   => storage_path('logs/slogger-traces'),
        ]);

        $storage->put(
            $parentTrace->loggedAt->toDateTimeString('microsecond') . '-' . $parentTrace->type->value . '.json',
            json_encode(array_values($traces), JSON_PRETTY_PRINT)
        );

        $this->traces = array_filter(
            $this->traces,
            fn(SLoggerTracePushDispatcherParameters $traceItem) => $traceItem->parentTraceId !== $parameters
        );
    }
}
