<?php

namespace App\Modules\Trace\Domain\Services;

use App\Modules\Trace\Domain\Entities\Objects\TraceObject;
use App\Modules\Trace\Domain\Entities\Objects\TraceServiceObject;
use App\Modules\Trace\Domain\Entities\Objects\Tree\TraceTreeObject;
use Illuminate\Support\Collection;

readonly class TraceTreeBuilder
{
    /**
     * @param Collection<TraceObject> $children
     */
    public function __construct(
        private TraceObject $parentTrace,
        private Collection $children
    ) {
    }

    public function build(): TraceTreeObject
    {
        return $this->traceToTraceTree($this->parentTrace, 0);
    }

    /**
     * @return TraceTreeObject[]
     */
    private function buildRecursive(TraceObject $parentTrace, int $depth): array
    {
        ++$depth;

        return $this->children
            ->filter(
                fn(TraceObject $childTrace) => $childTrace->parentTraceId === $parentTrace->traceId
            )
            ->map(
                fn(TraceObject $childTrace) => $this->traceToTraceTree($childTrace, $depth)
            )
            ->sortBy(
                fn(TraceTreeObject $traceTreeNodeObject) => $traceTreeNodeObject->loggedAt
                    ->toDateTimeString('microsecond')
            )
            ->values()
            ->toArray();
    }

    private function traceToTraceTree(TraceObject $trace, int $depth): TraceTreeObject
    {
        return new TraceTreeObject(
            service: $trace->service
                ? new TraceServiceObject(
                    id: $trace->service->id,
                    name: $trace->service->name,
                )
                : null,
            traceId: $trace->traceId,
            parentTraceId: $trace->parentTraceId,
            type: $trace->type,
            status: $trace->status,
            tags: $trace->tags,
            duration: $trace->duration,
            memory: $trace->memory,
            cpu: $trace->cpu,
            loggedAt: $trace->loggedAt,
            children: $this->buildRecursive($trace, $depth),
            depth: $depth
        );
    }
}
