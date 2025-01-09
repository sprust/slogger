<?php

namespace SLoggerGrpc\Services;

use Google\Protobuf\Internal\Message;
use Grpc\BaseStub;
use SLoggerGrpcDto\TraceCollector\TraceCollectorInterface;
use SLoggerGrpcDto\TraceCollector\TraceCollectorResponse;
use SLoggerGrpcDto\TraceCollector\TraceCreateRequest;
use SLoggerGrpcDto\TraceCollector\TraceUpdateRequest;
use Spiral\RoadRunner\GRPC;

class TraceCollectorGrpcService extends BaseStub implements TraceCollectorInterface
{
    /**
     * @throws GrpcResponseException
     */
    public function Create(GRPC\ContextInterface $ctx, TraceCreateRequest $in): TraceCollectorResponse
    {
        return $this->sendRequest(
            ctx: $ctx,
            in: $in,
            method: __FUNCTION__
        );
    }

    /**
     * @throws GrpcResponseException
     */
    public function Update(GRPC\ContextInterface $ctx, TraceUpdateRequest $in): TraceCollectorResponse
    {
        return $this->sendRequest(
            ctx: $ctx,
            in: $in,
            method: __FUNCTION__
        );
    }

    /**
     * @throws GrpcResponseException
     */
    private function sendRequest(
        GRPC\ContextInterface $ctx,
        Message $in,
        string $method
    ): TraceCollectorResponse {
        [$response, $status] = $this->_simpleRequest(
            '/' . self::NAME . '/' . $method,
            argument: $in,
            deserialize: [TraceCollectorResponse::class, 'decode'],
            metadata: (array) $ctx->getValue('metadata'),
            options: (array) $ctx->getValue('options')
        )->wait();

        $code = $status->code ?? GRPC\StatusCode::UNKNOWN;

        if ($code !== GRPC\StatusCode::OK) {
            throw new GRPC\Exception\GRPCException(
                message: $status->details,
                code: $status->code
            );
        }

        /** @var TraceCollectorResponse $response */

        if ($response->getStatusCode() !== 200) {
            throw new GrpcResponseException(
                message: $response->getMessage(),
                code: $response->getStatusCode()
            );
        }

        return $response;
    }
}
