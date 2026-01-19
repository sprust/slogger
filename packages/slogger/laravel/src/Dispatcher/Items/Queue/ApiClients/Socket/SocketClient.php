<?php

declare(strict_types=1);

namespace SLoggerLaravel\Dispatcher\Items\Queue\ApiClients\Socket;

use SLoggerLaravel\Dispatcher\Items\Queue\ApiClients\ApiClientInterface;
use SLoggerLaravel\Objects\TracesObject;
use SLoggerLaravel\Profiling\Dto\ProfilingObjects;

class SocketClient implements ApiClientInterface
{
    public function __construct(
        protected string $apiToken,
        protected Connection $connection,
        protected ArraySerializer $serializer
    ) {
    }

    public function sendTraces(TracesObject $traces): void
    {
        $this->connectIfNeed();

        $iterator = $traces->iterateCreating();

        $creatableTraces = [];

        foreach ($iterator as $trace) {
            $creatableTraces[] = [
                'trace_id'        => $trace->traceId,
                'parent_trace_id' => $trace->parentTraceId,
                'type'            => $trace->type,
                'status'          => $trace->status,
                'tags'            => $trace->tags,
                'data'            => $trace->data,
                'duration'        => $trace->duration,
                'memory'          => $trace->memory,
                'cpu'             => $trace->cpu,
                'is_parent'       => $trace->isParent,
                'logged_at'       => $trace->loggedAt->toDateTimeString('microsecond'),
            ];
        }

        $updatableTraces = [];

        $iterator = $traces->iterateUpdating();

        foreach ($iterator as $trace) {
            $updatableTraces[] = [
                'trace_id' => $trace->traceId,
                'status'   => $trace->status,
                ...(is_null($trace->profiling)
                    ? []
                    : ['profiling' => $this->prepareProfiling($trace->profiling)]),
                ...(is_null($trace->tags)
                    ? []
                    : ['tags' => $trace->tags]),
                ...(is_null($trace->data)
                    ? []
                    : ['data' => $trace->data]),
                ...(is_null($trace->duration)
                    ? []
                    : ['duration' => $trace->duration]),
                ...(is_null($trace->memory)
                    ? []
                    : ['memory' => $trace->memory]),
                ...(is_null($trace->cpu)
                    ? []
                    : ['cpu' => $trace->cpu]),
            ];
        }

        $this->connection->write(
            json_encode([
                'crt' => $this->serializer->serialize($creatableTraces),
                'upd' => $this->serializer->serialize($updatableTraces),
            ])
        );
    }

    /**
     * @return array{
     *     main_caller: string,
     *     items: array{
     *      raw: string,
     *      calling: string,
     *      callable: string,
     *      data: array{
     *          name: string,
     *          value: int|float
     *      }[]
     *     }[]
     * }
     */
    private function prepareProfiling(ProfilingObjects $profiling): array
    {
        $result = [];

        foreach ($profiling->getItems() as $item) {
            $result[] = [
                'raw'      => $item->raw,
                'calling'  => $item->calling,
                'callable' => $item->callable,
                'data'     => [
                    $this->makeProfileDataItem('wait (us)', $item->data->waitTimeInUs),
                    $this->makeProfileDataItem('calls', $item->data->numberOfCalls),
                    $this->makeProfileDataItem('cpu', $item->data->cpuTime),
                    $this->makeProfileDataItem('mem (b)', $item->data->memoryUsageInBytes),
                    $this->makeProfileDataItem('mem peak (b)', $item->data->peakMemoryUsageInBytes),
                ],
            ];
        }

        return [
            'main_caller' => $profiling->getMainCaller(),
            'items'       => $result,
        ];
    }

    /**
     * @return array{name: string, value: int|float}
     */
    private function makeProfileDataItem(string $name, int|float $value): array
    {
        return [
            'name'  => $name,
            'value' => $value,
        ];
    }

    protected function connectIfNeed(): void
    {
        if (!$this->connection->isConnected()) {
            $this->connection->connect(
                apiToken: $this->apiToken
            );
        }
    }
}
