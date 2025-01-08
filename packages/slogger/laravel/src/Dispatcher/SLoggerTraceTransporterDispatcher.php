<?php

namespace SLoggerLaravel\Dispatcher;

use Exception;
use Illuminate\Contracts\Queue\Queue;
use SLoggerLaravel\Objects\SLoggerTraceObject;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObject;
use Illuminate\Support\Facades\Queue as QueueFacade;
use SLoggerLaravel\Profiling\Dto\SLoggerProfilingObjects;

class SLoggerTraceTransporterDispatcher implements SLoggerTraceDispatcherInterface
{
    /** @var SLoggerTraceObject[] */
    private array $traces = [];

    private int $maxBatchSize = 5;

    private string $token;

    private Queue $connection;
    private string $queue;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->token = config('slogger.token');

        $this->connection = QueueFacade::connection(config('slogger.queue_transporter.connection'));
        $this->queue      = config('slogger.queue_transporter.name');
    }

    public function push(SLoggerTraceObject $parameters): void
    {
        $this->traces[] = $parameters;

        if (count($this->traces) < $this->maxBatchSize) {
            return;
        }

        $payload = array_map(
            fn(SLoggerTraceObject $trace) => $this->makeCreateData($trace),
            $this->traces
        );

        $this->traces = [];

        $this->dispatch($payload);
    }

    public function stop(SLoggerTraceUpdateObject $parameters): void
    {
        $payload = [];

        if (count($this->traces)) {
            $payload = array_map(
                fn(SLoggerTraceObject $trace) => $this->makeCreateData($trace),
                $this->traces
            );
        }

        $this->traces = [];

        $payload[] = $this->makeUpdateData($parameters);

        $this->dispatch($payload);
    }

    private function makeCreateData(SLoggerTraceObject $trace): array
    {
        return $this->makeAction(
            type: 'cr',
            data: $this->traceCreateToJson($trace)
        );
    }

    private function makeUpdateData(SLoggerTraceUpdateObject $trace): array
    {
        return $this->makeAction(
            type: 'upd',
            data: $this->traceUpdateToJson($trace)
        );
    }

    private function traceCreateToJson(SLoggerTraceObject $trace): string
    {
        return json_encode([
            'tid' => $trace->traceId,
            'pid' => $trace->parentTraceId,
            'tp'  => $trace->type,
            'st'  => $trace->status,
            'tgs' => $trace->tags,
            'dt'  => json_encode($trace->data),
            'dur' => $trace->duration,
            'mem' => $trace->memory,
            'cpu' => $trace->cpu,
            'lat' => $trace->loggedAt->clone()
                ->setTimezone('UTC')
                ->toDateTimeString('microsecond'),
        ]);
    }

    private function traceUpdateToJson(SLoggerTraceUpdateObject $trace): string
    {
        return json_encode([
            'tid' => $trace->traceId,
            'st'  => $trace->status,
            'pr'  => $trace->profiling
                ? $this->prepareProfiling($trace->profiling)
                : null,
            'tgs' => $trace->tags,
            'dt'  => $trace->data,
            'dur' => $trace->duration,
            'mem' => $trace->memory,
            'cpu' => $trace->cpu,
        ]);
    }

    private function makeAction(string $type, string $data): array
    {
        return [
            'tp' => $type,
            'dt' => $data,
        ];
    }

    private function prepareProfiling(SLoggerProfilingObjects $profiling): array
    {
        $result = [];

        foreach ($profiling->getItems() as $item) {
            $result[] = [
                'raw'   => $item->raw,
                'c_ing' => $item->calling,
                'c_ble' => $item->callable,
                'dt'    => [
                    $this->makeProfileDataItem('wait (us)', $item->data->waitTimeInUs),
                    $this->makeProfileDataItem('calls', $item->data->numberOfCalls),
                    $this->makeProfileDataItem('cpu', $item->data->cpuTime),
                    $this->makeProfileDataItem('mem (b)', $item->data->memoryUsageInBytes),
                    $this->makeProfileDataItem('mem peak (b)', $item->data->peakMemoryUsageInBytes),
                ],
            ];
        }

        return [
            'mc'  => $profiling->getMainCaller(),
            'its' => $result,
        ];
    }

    private function makeProfileDataItem(string $name, int|float $value): array
    {
        return [
            'nm'  => $name,
            'val' => $value,
        ];
    }

    private function dispatch(array $actions): void
    {
        $this->connection->pushRaw(
            payload: json_encode([
                'id'      => uniqid(),
                'payload' => json_encode([
                    'tok' => $this->token,
                    'acs' => $actions,
                ]),
            ]),
            queue: $this->queue
        );
    }
}
