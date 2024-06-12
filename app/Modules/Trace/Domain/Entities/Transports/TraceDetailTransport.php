<?php

namespace App\Modules\Trace\Domain\Entities\Transports;

use App\Modules\Trace\Domain\Entities\Objects\TraceDetailObject;
use App\Modules\Trace\Domain\Entities\Objects\TraceObject;
use App\Modules\Trace\Domain\Entities\Objects\TraceServiceObject;
use App\Modules\Trace\Repositories\Dto\TraceDetailDto;

class TraceDetailTransport
{
    public static function toObject(TraceDetailDto $dto): TraceObject
    {
        return new TraceObject(
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
