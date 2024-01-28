<?php

namespace SLoggerLaravel\Dispatcher;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;

/**
 * @example
 */
class SLoggerTraceLogDispatcher implements TraceDispatcherInterface
{
    /** @var TracePushDispatcherParameters[] */
    private array $traces = [];

    public function __construct(private readonly Application $app)
    {
    }

    public function push(TracePushDispatcherParameters $parameters): void
    {
        $this->traces[] = $parameters;
    }

    public function stop(TraceStopDispatcherParameters $parameters): void
    {
        if (!$this->traces) {
            return;
        }

        $filtered = array_filter(
            $this->traces,
            fn(TracePushDispatcherParameters $traceItem) => $traceItem->parentTraceId === $parameters->traceId
                || $traceItem->traceId === $parameters->traceId
        );

        $traces = Arr::sort(
            $filtered,
            fn(TracePushDispatcherParameters $traceItem) => $traceItem->loggedAt->getTimestampMs()
        );

        /** @var TracePushDispatcherParameters $parentTrace */
        $parentTrace = Arr::first(
            $traces,
            fn(TracePushDispatcherParameters $traceItem) => $traceItem->traceId === $parameters->traceId
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
            fn(TracePushDispatcherParameters $traceItem) => $traceItem->parentTraceId !== $parameters
        );
    }
}
