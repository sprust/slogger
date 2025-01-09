<?php

namespace SLoggerLaravel\Dispatcher;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use SLoggerLaravel\Dispatcher\File\SLoggerTraceFileDispatcher;
use SLoggerLaravel\Dispatcher\Queue\ApiClients\SLoggerApiClientFactory;
use SLoggerLaravel\Dispatcher\Queue\ApiClients\SLoggerApiClientInterface;
use SLoggerLaravel\Dispatcher\Queue\SLoggerTraceQueueDispatcher;
use SLoggerLaravel\Dispatcher\Transporter\Clients\SLoggerTransporterClient;
use SLoggerLaravel\Dispatcher\Transporter\Clients\SLoggerTransporterClientInterface;
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
            'queue' => $this->createQueue(),
            'transporter' => $this->createTransporter(),
            'file' => $this->app->make(SLoggerTraceFileDispatcher::class),
            default => throw new RuntimeException("Unknown dispatcher: $dispatcher"),
        };
    }

    /**
     * @throws BindingResolutionException
     */
    private function createQueue(): SLoggerTraceDispatcherInterface
    {
        $this->app->singleton(SLoggerApiClientInterface::class, function (Application $app) {
            return $app->make(SLoggerApiClientFactory::class)->create(
                config('slogger.dispatchers.queue.api_clients.default')
            );
        });

        return $this->app->make(SLoggerTraceQueueDispatcher::class);
    }

    /**
     * @throws BindingResolutionException
     */
    private function createTransporter(): SLoggerTraceDispatcherInterface
    {
        $this->app->singleton(SLoggerTransporterClientInterface::class, function (Application $app) {
            return new SLoggerTransporterClient(
                apiToken: config('slogger.token'),
                connection: Queue::connection(config('slogger.dispatchers.transporter.queue.connection')),
                queueName: config('slogger.dispatchers.transporter.queue.name')
            );
        });

        return $this->app->make(SLoggerTraceTransporterDispatcher::class);
    }
}
