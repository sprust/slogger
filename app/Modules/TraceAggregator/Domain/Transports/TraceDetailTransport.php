<?php

namespace App\Modules\TraceAggregator\Domain\Transports;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceDetailObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceServiceObject;
use App\Modules\TraceAggregator\Repositories\Dto\TraceDetailDto;

class TraceDetailTransport
{
    public static function toObject(TraceDetailDto $dto): TraceObject
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

    public static function toDetailObject(TraceDetailDto $dto): TraceDetailObject
    {
        return new TraceDetailObject(
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
            data: (new TraceDataToObjectBuilder($dto->data))->build(),
            duration: $dto->duration,
            memory: $dto->memory,
            cpu: $dto->cpu,
            loggedAt: $dto->loggedAt,
            createdAt: $dto->createdAt,
            updatedAt: $dto->updatedAt
        );
    }
}
