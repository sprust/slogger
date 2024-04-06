<?php

namespace App\Modules\TraceAggregator\Domain\Services;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceServiceObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTreeObject;
use App\Modules\TraceAggregator\Repositories\Dto\TraceDto;
use Illuminate\Support\Collection;

readonly class TraceTreeBuilder
{
    /**
     * @param Collection<TraceDto> $children
     */
    public function __construct(
        private TraceDto $parentTrace,
        private Collection $children
    ) {
    }

    public function collect(): TraceTreeObject
    {
        return new TraceTreeObject(
            serviceObject: $this->parentTrace->service
                ? new TraceServiceObject(
                    id: $this->parentTrace->service->id,
                    name: $this->parentTrace->service->name,
                )
                : null,
            traceId: $this->parentTrace->traceId,
            parentTraceId: $this->parentTrace->parentTraceId,
            type: $this->parentTrace->type,
            status: $this->parentTrace->status,
            tags: $this->parentTrace->tags,
            duration: $this->parentTrace->duration,
            memory: $this->parentTrace->memory,
            cpu: $this->parentTrace->cpu,
            loggedAt: $this->parentTrace->loggedAt,
            children: $this->collectRecursive($this->parentTrace, 0),
            depth: 0
        );
    }

    /**
     * @return TraceTreeObject[]
     */
    private function collectRecursive(TraceDto $parentTrace, int $depth): array
    {
        ++$depth;

        return $this->children
            ->filter(
                fn(TraceDto $childTrace) => $childTrace->parentTraceId === $parentTrace->traceId
            )
            ->map(
                fn(TraceDto $childTrace) => new TraceTreeObject(
                    serviceObject: $childTrace->service
                        ? new TraceServiceObject(
                            id: $childTrace->service->id,
                            name: $childTrace->service->name,
                        )
                        : null,
                    traceId: $childTrace->traceId,
                    parentTraceId: $childTrace->parentTraceId,
                    type: $childTrace->type,
                    status: $childTrace->status,
                    tags: $childTrace->tags,
                    duration: $childTrace->duration,
                    memory: $childTrace->memory,
                    cpu: $childTrace->cpu,
                    loggedAt: $childTrace->loggedAt,
                    children: $this->collectRecursive($childTrace, $depth),
                    depth: $depth
                )
            )
            ->sortBy(
                fn(TraceTreeObject $traceTreeNodeObject) => $traceTreeNodeObject->loggedAt
                    ->toDateTimeString('microsecond')
            )
            ->values()
            ->toArray();
    }
}
