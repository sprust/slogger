<?php

namespace SLoggerLaravel\Dispatcher\Transporter;

use Exception;
use SLoggerLaravel\Dispatcher\SLoggerTraceDispatcherInterface;
use SLoggerLaravel\Dispatcher\Transporter\Clients\SLoggerTransporterClientInterface;
use SLoggerLaravel\Objects\SLoggerTraceObject;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObject;
use SLoggerLaravel\Profiling\Dto\SLoggerProfilingObjects;

class SLoggerTraceTransporterDispatcher implements SLoggerTraceDispatcherInterface
{
    /** @var SLoggerTraceObject[] */
    private array $traces = [];

    private int $maxBatchSize = 5;

    /**
     * @throws Exception
     */
    public function __construct(
        private readonly SLoggerTransporterClientInterface $client
    ) {
    }

    public function push(SLoggerTraceObject $parameters): void
    {
        $this->traces[] = $parameters;

        if (count($this->traces) < $this->maxBatchSize) {
            return;
        }

        $actions = array_map(
            fn(SLoggerTraceObject $trace) => $this->makeCreateData($trace),
            $this->traces
        );

        $this->traces = [];

        $this->client->dispatch($actions);
    }

    public function stop(SLoggerTraceUpdateObject $parameters): void
    {
        $actions = [];

        if (count($this->traces)) {
            $actions = array_map(
                fn(SLoggerTraceObject $trace) => $this->makeCreateData($trace),
                $this->traces
            );
        }

        $this->traces = [];

        $actions[] = $this->makeUpdateData($parameters);

        $this->client->dispatch($actions);
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
}
