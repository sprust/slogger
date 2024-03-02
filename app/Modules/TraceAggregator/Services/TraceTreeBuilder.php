<?php

namespace App\Modules\TraceAggregator\Services;

use App\Models\Traces\Trace;
use App\Modules\TraceAggregator\Dto\Objects\TraceServiceObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceTreeObject;
use Illuminate\Support\Collection;

class TraceTreeBuilder
{
    /**
     * @var Collection<string, Trace>
     */
    private Collection $childrenMap;

    /**
     * @param Collection<Trace> $children
     */
    public function __construct(
        private readonly Trace $parentTrace,
        private readonly Collection $children
    ) {
    }

    public function collect(): TraceTreeObject
    {
        $this->childrenMap = $this->children->keyBy(
            fn(Trace $trace) => $trace->traceId
        );

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
    private function collectRecursive(Trace $parentTrace, int $depth): array
    {
        ++$depth;

        return $this->childrenMap
            ->filter(
                fn(Trace $childTrace) => $childTrace->parentTraceId === $parentTrace->traceId
            )
            ->map(
                fn(Trace $childTrace) => new TraceTreeObject(
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
