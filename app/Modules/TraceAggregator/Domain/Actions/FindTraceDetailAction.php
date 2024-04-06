<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceDetailObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceServiceObject;
use App\Modules\TraceAggregator\Domain\Services\TraceDataToObjectConverter;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceRepositoryInterface;

readonly class FindTraceDetailAction
{
    public function __construct(
        private TraceRepositoryInterface $repository
    ) {
    }

    public function handle(string $traceId): ?TraceDetailObject
    {
        $traceDetailDto = $this->repository->findOneByTraceId($traceId);

        return new TraceDetailObject(
            service: $traceDetailDto->service
                ? new TraceServiceObject(
                    id: $traceDetailDto->service->id,
                    name: $traceDetailDto->service->name,
                )
                : null,
            traceId: $traceDetailDto->traceId,
            parentTraceId: $traceDetailDto->parentTraceId,
            type: $traceDetailDto->type,
            status: $traceDetailDto->status,
            tags: $traceDetailDto->tags,
            data: (new TraceDataToObjectConverter($traceDetailDto->data))->convert(),
            duration: $traceDetailDto->duration,
            memory: $traceDetailDto->memory,
            cpu: $traceDetailDto->cpu,
            loggedAt: $traceDetailDto->loggedAt,
            createdAt: $traceDetailDto->createdAt,
            updatedAt: $traceDetailDto->updatedAt
        );
    }
}
