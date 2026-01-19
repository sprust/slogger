<?php

namespace SLoggerLaravel\Dispatcher\Items\Queue\ApiClients\Grpc;

use Google\Protobuf\BoolValue;
use Google\Protobuf\DoubleValue;
use Google\Protobuf\Int32Value;
use Google\Protobuf\StringValue;
use Google\Protobuf\Timestamp;
use SLoggerGrpc\Services\GrpcResponseException;
use SLoggerGrpc\Services\TraceCollectorGrpcService;
use SLoggerGrpcDto\TraceCollector\TagsObject;
use SLoggerGrpcDto\TraceCollector\TraceCreateObject;
use SLoggerGrpcDto\TraceCollector\TraceCreateRequest;
use SLoggerGrpcDto\TraceCollector\TraceProfilingItemDataItemObject;
use SLoggerGrpcDto\TraceCollector\TraceProfilingItemDataItemValueObject;
use SLoggerGrpcDto\TraceCollector\TraceProfilingItemObject;
use SLoggerGrpcDto\TraceCollector\TraceProfilingItemsObject;
use SLoggerGrpcDto\TraceCollector\TraceUpdateObject;
use SLoggerGrpcDto\TraceCollector\TraceUpdateRequest;
use SLoggerLaravel\Dispatcher\Items\Queue\ApiClients\ApiClientInterface;
use SLoggerLaravel\Objects\TracesObject;
use SLoggerLaravel\Profiling\Dto\ProfilingObjects;
use Spiral\RoadRunner\GRPC\Context;

readonly class GrpcClient implements ApiClientInterface
{
    public function __construct(
        private string $apiToken,
        private TraceCollectorGrpcService $grpcService
    ) {
    }

    /**
     * @throws GrpcResponseException
     */
    public function sendTraces(TracesObject $traces): void
    {
        $this->createTraces($traces);
        $this->updateTraces($traces);
    }

    /**
     * @throws GrpcResponseException
     */
    protected function createTraces(TracesObject $traces): void
    {
        $objects = [];

        foreach ($traces->iterateCreating() as $trace) {
            $loggedAt = new Timestamp();
            $loggedAt->fromDateTime($trace->loggedAt->toDateTime());

            $objects[] = (new TraceCreateObject())
                ->setTraceId($trace->traceId)
                ->setParentTraceId(
                    is_null($trace->parentTraceId)
                        ? null
                        : new StringValue(['value' => $trace->parentTraceId])
                )
                ->setType($trace->type)
                ->setStatus($trace->status)
                ->setTags($trace->tags)
                ->setData(json_encode($trace->data))
                ->setDuration(
                    is_null($trace->duration)
                        ? null
                        : new DoubleValue(['value' => $trace->duration])
                )
                ->setMemory(
                    is_null($trace->memory)
                        ? null
                        : new DoubleValue(['value' => $trace->memory])
                )
                ->setCpu(
                    is_null($trace->cpu)
                        ? null
                        : new DoubleValue(['value' => $trace->cpu])
                )
                ->setIsParent(new BoolValue(['value' => $trace->isParent]))
                ->setLoggedAt($loggedAt);
        }

        if (count($objects) == 0) {
            return;
        }

        $this->grpcService->Create(
            new Context([
                'metadata' => [
                    'authorization' => [
                        "Bearer $this->apiToken",
                    ],
                ],
            ]),
            new TraceCreateRequest([
                'traces' => $objects,
            ])
        );
    }

    /**
     * @throws GrpcResponseException
     */
    protected function updateTraces(TracesObject $traces): void
    {
        $objects = [];

        foreach ($traces->iterateUpdating() as $item) {
            $loggedAt = new Timestamp();
            $loggedAt->fromDateTime(now('UTC'));

            $objects[] = (new TraceUpdateObject())
                ->setTraceId($item->traceId)
                ->setStatus($item->status)
                ->setProfiling(
                    is_null($item->profiling)
                        ? null
                        : $this->makeProfiling($item->profiling)
                )
                ->setTags(
                    is_null($item->tags)
                        ? null
                        : new TagsObject(['items' => $item->tags])
                )
                ->setData(
                    is_null($item->data)
                        ? null
                        : new StringValue(['value' => json_encode($item->data)])
                )
                ->setDuration(
                    is_null($item->duration)
                        ? null
                        : new DoubleValue(['value' => $item->duration])
                )
                ->setMemory(
                    is_null($item->memory)
                        ? null
                        : new DoubleValue(['value' => $item->memory])
                )
                ->setCpu(
                    is_null($item->cpu)
                        ? null
                        : new DoubleValue(['value' => $item->cpu])
                );
        }

        if (count($objects) == 0) {
            return;
        }

        $this->grpcService->Update(
            new Context([
                'metadata' => [
                    'authorization' => [
                        "Bearer $this->apiToken",
                    ],
                ],
            ]),
            new TraceUpdateRequest([
                'traces' => $objects,
            ])
        );
    }

    private function makeProfiling(ProfilingObjects $profiling): TraceProfilingItemsObject
    {
        /** @var TraceProfilingItemObject[] $items */
        $items = [];

        foreach ($profiling->getItems() as $item) {
            $items[] = (new TraceProfilingItemObject())
                ->setRaw($item->raw)
                ->setCalling($item->calling)
                ->setCallable($item->callable)
                ->setData([
                    (new TraceProfilingItemDataItemObject())
                        ->setName('wait (us)')
                        ->setValue(
                            (new TraceProfilingItemDataItemValueObject())
                                ->setInt(
                                    new Int32Value([
                                        'value' => $item->data->waitTimeInUs,
                                    ])
                                )
                        ),
                    (new TraceProfilingItemDataItemObject())
                        ->setName('calls')
                        ->setValue(
                            (new TraceProfilingItemDataItemValueObject())
                                ->setDouble(
                                    new DoubleValue([
                                        'value' => $item->data->numberOfCalls,
                                    ])
                                )
                        ),
                    (new TraceProfilingItemDataItemObject())
                        ->setName('cpu')
                        ->setValue(
                            (new TraceProfilingItemDataItemValueObject())
                                ->setDouble(
                                    new DoubleValue([
                                        'value' => $item->data->cpuTime,
                                    ])
                                )
                        ),
                    (new TraceProfilingItemDataItemObject())
                        ->setName('mem (b)')
                        ->setValue(
                            (new TraceProfilingItemDataItemValueObject())
                                ->setDouble(
                                    new DoubleValue([
                                        'value' => $item->data->memoryUsageInBytes,
                                    ])
                                )
                        ),
                    (new TraceProfilingItemDataItemObject())
                        ->setName('mem peak (b)')
                        ->setValue(
                            (new TraceProfilingItemDataItemValueObject())
                                ->setDouble(
                                    new DoubleValue([
                                        'value' => $item->data->peakMemoryUsageInBytes,
                                    ])
                                )
                        ),
                ]);
        }

        return (new TraceProfilingItemsObject())
            ->setMainCaller($profiling->getMainCaller())
            ->setItems($items);
    }
}
