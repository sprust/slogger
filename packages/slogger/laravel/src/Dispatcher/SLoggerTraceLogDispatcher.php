<?php

namespace SLoggerLaravel\Dispatcher;

use Illuminate\Contracts\Foundation\Application;

/**
 * @example
 */
class SLoggerTraceLogDispatcher implements TraceDispatcherInterface
{
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
        $this->app['log']->debug(json_encode($this->trace, JSON_PRETTY_PRINT));

        $this->trace = [];
    }
}
