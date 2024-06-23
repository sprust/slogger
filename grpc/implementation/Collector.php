<?php

namespace GRPCServices;

use GRPC\Collector\TraceCollectorInterface;
use GRPC\Collector\TraceCreateRequest;
use GRPC\Collector\TraceCreateResponse;
use Spiral\RoadRunner\GRPC;

class Collector implements TraceCollectorInterface
{
    public function Create(GRPC\ContextInterface $ctx, TraceCreateRequest $in): TraceCreateResponse
    {
        return new TraceCreateResponse([
            'status_code' => 200,
            'message'     => 'Ok',
        ]);
    }
}
