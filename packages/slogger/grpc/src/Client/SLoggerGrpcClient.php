<?php

namespace GRPCClient;

use Google\Protobuf\DoubleValue;
use Google\Protobuf\StringValue;
use Google\Protobuf\Timestamp;
use GRPC\TraceCollector\TagsObject;
use GRPC\TraceCollector\TraceCreateObject;
use GRPC\TraceCollector\TraceCreateRequest;
use GRPC\TraceCollector\TraceUpdateObject;
use GRPC\TraceCollector\TraceUpdateRequest;
use GRPCClient\Services\SLoggerGrpcResponseException;
use GRPCClient\Services\SLoggerTraceCollectorGrpcService;
use SLoggerLaravel\ApiClients\SLoggerApiClientInterface;
use SLoggerLaravel\Objects\SLoggerTraceObjects;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObjects;
use Spiral\RoadRunner\GRPC\Context;

readonly class SLoggerGrpcClient implements SLoggerApiClientInterface
{
    public function __construct(
        private string $apiToken,
        private SLoggerTraceCollectorGrpcService $grpcService
    ) {
    }

    /**
     * @throws SLoggerGrpcResponseException
     */
    public function sendTraces(SLoggerTraceObjects $traceObjects): void
    {
        $objects = [];

        foreach ($traceObjects->get() as $item) {
            $loggedAt = new Timestamp();
            $loggedAt->fromDateTime(now('UTC'));

            $objects[] = (new TraceCreateObject())
                ->setTraceId($item->traceId)
                ->setParentTraceId(
                    is_null($item->parentTraceId,)
                        ? null
                        : new StringValue(['value' => $item->parentTraceId])
                )
                ->setType($item->type)
                ->setStatus($item->status)
                ->setTags($item->tags)
                ->setData(json_encode($item->data))
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
                )
                ->setLoggedAt($loggedAt);
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

    public function updateTraces(SLoggerTraceUpdateObjects $traceObjects): void
    {
        $objects = [];

        foreach ($traceObjects->get() as $item) {
            $loggedAt = new Timestamp();
            $loggedAt->fromDateTime(now('UTC'));

            $objects[] = (new TraceUpdateObject())
                ->setTraceId($item->traceId)
                ->setStatus($item->status)
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
}
