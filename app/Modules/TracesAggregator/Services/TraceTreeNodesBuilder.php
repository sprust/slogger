<?php

namespace App\Modules\TracesAggregator\Services;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Dto\Objects\TraceTreeNodeObject;
use App\Modules\TracesAggregator\Dto\TraceServiceObject;
use Illuminate\Support\Collection;

class TraceTreeNodesBuilder
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

    public function collect(): TraceTreeNodeObject
    {
        $this->childrenMap = $this->children->keyBy(
            fn(Trace $trace) => $trace->traceId
        );

        return new TraceTreeNodeObject(
            serviceObject: $this->parentTrace->service
                ? new TraceServiceObject(
                    id: $this->parentTrace->service->id,
                    name: $this->parentTrace->service->name,
                )
                : null,
            traceId: $this->parentTrace->traceId,
            parentTraceId: $this->parentTrace->parentTraceId,
            type: $this->parentTrace->type,
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
     * @return TraceTreeNodeObject[]
     */
    private function collectRecursive(Trace $parentTrace, int $depth): array
    {
        ++$depth;

        return $this->childrenMap
            ->filter(
                fn(Trace $childTrace) => $childTrace->parentTraceId === $parentTrace->traceId
            )
            ->map(
                fn(Trace $childTrace) => new TraceTreeNodeObject(
                    serviceObject: $childTrace->service
                        ? new TraceServiceObject(
                            id: $childTrace->service->id,
                            name: $childTrace->service->name,
                        )
                        : null,
                    traceId: $childTrace->traceId,
                    parentTraceId: $childTrace->parentTraceId,
                    type: $childTrace->type,
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
                fn(TraceTreeNodeObject $traceTreeNodeObject) => $traceTreeNodeObject->loggedAt->microsecond
            )
            ->values()
            ->toArray();
    }
}
