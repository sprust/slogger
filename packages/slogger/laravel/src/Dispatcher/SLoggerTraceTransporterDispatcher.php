<?php

namespace SLoggerLaravel\Dispatcher;

use Illuminate\Support\Facades\Queue;
use SLoggerLaravel\Objects\SLoggerTraceObject;
use SLoggerLaravel\Objects\SLoggerTraceObjects;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObject;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObjects;

class SLoggerTraceTransporterDispatcher implements SLoggerTraceDispatcherInterface
{
    /** @var SLoggerTraceObject[] */
    private array $traces = [];

    private int $maxBatchSize = 5;

    private string $token;
    private string $connection;
    private string $queue;

    public function __construct()
    {
        $this->token      = config('slogger.token');
        $this->connection = config('slogger.queue.connection');
        $this->queue      = config('slogger.queue.name');
    }

    public function push(SLoggerTraceObject $parameters): void
    {
        $this->traces[] = $parameters;

        if (count($this->traces) < $this->maxBatchSize) {
            return;
        }

        $this->sendAndClearTraces();
    }

    public function stop(SLoggerTraceUpdateObject $parameters): void
    {
        if (count($this->traces)) {
            $this->sendAndClearTraces();
        }

        $traceObjects = (new SLoggerTraceUpdateObjects())
            ->add($parameters);

        $this->dispatch('stop', $traceObjects->toJson());
    }

    protected function sendAndClearTraces(): void
    {
        $traceObjects = new SLoggerTraceObjects();

        foreach ($this->traces as $trace) {
            $traceObjects->add($trace);
        }

        $this->dispatch('push', $traceObjects->toJson());

        $this->traces = [];
    }

    private function dispatch(string $action, string $payload): void
    {
        Queue::connection($this->connection)
            ->pushRaw(
                payload: json_encode([
                    'token'   => $this->token,
                    'action'  => $action,
                    'payload' => $payload,
                    'tries'   => 0,
                ]),
                queue: $this->queue
            );
    }
}
