<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Transports;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceServiceObject;
use App\Modules\TraceAggregator\Repositories\Dto\TraceDto;

class TraceTransport
{
    public static function toObject(TraceDto $dto): TraceObject
    {
        return new TraceObject(
            id: $dto->id,
            service: $dto->service
                ? new TraceServiceObject(
                    id: $dto->service->id,
                    name: $dto->service->name,
                )
                : null,
            traceId: $dto->traceId,
            parentTraceId: $dto->parentTraceId,
            type: $dto->type,
            status: $dto->status,
            tags: $dto->tags,
            duration: $dto->duration,
            memory: $dto->memory,
            cpu: $dto->cpu,
            loggedAt: $dto->loggedAt,
            createdAt: $dto->createdAt,
            updatedAt: $dto->updatedAt
        );
    }
}
