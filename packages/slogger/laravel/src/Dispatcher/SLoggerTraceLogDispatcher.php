<?php

namespace SLoggerLaravel\Dispatcher;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;

/**
 * @example
 */
class SLoggerTraceLogDispatcher implements TraceDispatcherInterface
{
    /** @var TraceDispatcherParameters[] */
    private array $trace = [];

    public function __construct(private readonly Application $app)
    {
    }

    public function put(TraceDispatcherParameters $parameters): void
    {
        $this->trace[] = $parameters;
    }

    public function stop(): void
    {
        if (!$this->trace) {
            return;
        }

        $trace = Arr::sort(
            $this->trace,
            function (TraceDispatcherParameters $parameters) {
                return $parameters->loggedAt->getTimestampMs();
            }
        );

        $this->app['log']->debug(json_encode(array_values($trace), JSON_PRETTY_PRINT));

        $this->trace = [];
    }
}
