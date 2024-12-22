<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceDetailActionInterface;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceServicesActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Entities\Trace\TraceDetailObject;

readonly class FindTraceDetailAction implements FindTraceDetailActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $repository,
        private FindTraceServicesActionInterface $findTraceServicesAction
    ) {
    }

    public function handle(string $traceId): ?TraceDetailObject
    {
        $trace = $this->repository->findOneDetailByTraceId($traceId);

        if (!$trace) {
            return null;
        }

        $service = $trace->serviceId
            ? $this->findTraceServicesAction->handle([$trace->serviceId])
                ->getById($trace->serviceId)
            : null;

        return new TraceDetailObject(
            id: $trace->id,
            service: $service,
            traceId: $trace->traceId,
            parentTraceId: $trace->parentTraceId,
            type: $trace->type,
            status: $trace->status,
            tags: $trace->tags,
            data: $trace->data,
            duration: $trace->duration,
            memory: $trace->memory,
            cpu: $trace->cpu,
            hasProfiling: $trace->hasProfiling,
            loggedAt: $trace->loggedAt,
            createdAt: $trace->createdAt,
            updatedAt: $trace->updatedAt,
        );
    }
}
