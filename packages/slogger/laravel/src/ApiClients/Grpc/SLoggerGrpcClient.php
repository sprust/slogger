<?php

namespace SLoggerLaravel\ApiClients\Grpc;

use Google\Protobuf\DoubleValue;
use Google\Protobuf\StringValue;
use Google\Protobuf\Timestamp;
use GRPC\Collector\TraceCreateObject;
use GRPC\Collector\TraceCreateRequest;
use SLoggerLaravel\ApiClients\Grpc\Services\SLoggerGrpcResponseException;
use SLoggerLaravel\ApiClients\Grpc\Services\SLoggerTraceCollectorGrpcService;
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
                ->setParentTraceId(new StringValue($item->parentTraceId))
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
    }
}
