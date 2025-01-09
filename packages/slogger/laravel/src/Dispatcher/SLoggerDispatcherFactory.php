<?php

namespace SLoggerLaravel\Dispatcher;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use RuntimeException;
use SLoggerLaravel\Dispatcher\File\SLoggerTraceFileDispatcher;
use SLoggerLaravel\Dispatcher\Queue\SLoggerTraceQueueDispatcher;
use SLoggerLaravel\Dispatcher\Transporter\SLoggerTraceTransporterDispatcher;

readonly class SLoggerDispatcherFactory
{
    public function __construct(private Application $app)
    {
    }

    /**
     * @throws BindingResolutionException
     */
    public function create(string $dispatcher): SLoggerTraceDispatcherInterface
    {
        return match ($dispatcher) {
            'queue' => $this->app->make(SLoggerTraceQueueDispatcher::class),
            'transporter' => $this->app->make(SLoggerTraceTransporterDispatcher::class),
            'file' => $this->app->make(SLoggerTraceFileDispatcher::class),
            default => throw new RuntimeException("Unknown dispatcher: $dispatcher"),
        };
    }
}
