<?php

namespace GRPCClient\Services;

use Google\Protobuf\Internal\Message;
use Grpc\BaseStub;
use GRPC\TraceCollector\TraceCollectorInterface;
use GRPC\TraceCollector\TraceCollectorResponse;
use GRPC\TraceCollector\TraceCreateRequest;
use GRPC\TraceCollector\TraceUpdateRequest;
use Spiral\RoadRunner\GRPC;

class SLoggerTraceCollectorGrpcService extends BaseStub implements TraceCollectorInterface
{
    /**
     * @throws SLoggerGrpcResponseException
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
     * @throws SLoggerGrpcResponseException
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
     * @throws SLoggerGrpcResponseException
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
            throw new SLoggerGrpcResponseException(
                message: $response->getMessage(),
                code: $response->getStatusCode()
            );
        }

        return $response;
    }
}
