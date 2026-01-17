<?php

declare(strict_types=1);

namespace SLoggerLaravel\Dispatcher\Items\Queue\ApiClients\Socket;

use SLoggerLaravel\Dispatcher\Items\Queue\ApiClients\ApiClientInterface;
use SLoggerLaravel\Objects\TraceObjects;
use SLoggerLaravel\Objects\TraceUpdateObjects;
use SLoggerLaravel\Profiling\Dto\ProfilingObjects;

class SocketClient implements ApiClientInterface
{
    public function __construct(
        protected string $apiToken,
        protected Connection $connection,
        protected ArraySerializer $serializer
    ) {
    }

    public function sendTraces(TraceObjects $traceObjects): void
    {
        $this->connectIfNeed();

        $traces = [];

        foreach ($traceObjects->get() as $traceObject) {
            $traces[] = [
                'trace_id'        => $traceObject->traceId,
                'parent_trace_id' => $traceObject->parentTraceId,
                'type'            => $traceObject->type,
                'status'          => $traceObject->status,
                'tags'            => $traceObject->tags,
                'data'            => $traceObject->data,
                'duration'        => $traceObject->duration,
                'memory'          => $traceObject->memory,
                'cpu'             => $traceObject->cpu,
                'is_parent'       => $traceObject->isParent,
                'logged_at'       => $traceObject->loggedAt->toDateTimeString('microsecond'),
            ];
        }

        $data = $this->serializer->serialize($traces);

        $this->connection->write($data);
    }

    public function updateTraces(TraceUpdateObjects $traceObjects): void
    {
        $this->connectIfNeed();

        $traces = [];

        foreach ($traceObjects->get() as $traceObject) {
            $traces[] = [
                'trace_id' => $traceObject->traceId,
                'status'   => $traceObject->status,
                ...(is_null($traceObject->profiling)
                    ? []
                    : ['profiling' => $this->prepareProfiling($traceObject->profiling)]),
                ...(is_null($traceObject->tags)
                    ? []
                    : ['tags' => $traceObject->tags]),
                ...(is_null($traceObject->data)
                    ? []
                    : ['data' => json_encode($traceObject->data)]),
                ...(is_null($traceObject->duration)
                    ? []
                    : ['duration' => $traceObject->duration]),
                ...(is_null($traceObject->memory)
                    ? []
                    : ['memory' => $traceObject->memory]),
                ...(is_null($traceObject->cpu)
                    ? []
                    : ['cpu' => $traceObject->cpu]),
            ];
        }

        $data = $this->serializer->serialize($traces);

        $this->connection->write($data);
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
