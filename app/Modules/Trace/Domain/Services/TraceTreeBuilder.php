<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Services;

use App\Modules\Trace\Entities\Trace\TraceObject;
use App\Modules\Trace\Entities\Trace\TraceServiceObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeObject;

readonly class TraceTreeBuilder
{
    /**
     * @param TraceObject[] $children
     */
    public function __construct(
        private TraceObject $parentTrace,
        private array $children
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

        $items = array_filter(
            $this->children,
            static fn(TraceObject $childTrace) => $childTrace->parentTraceId === $parentTrace->traceId
        );

        $items = array_map(
            fn(TraceObject $childTrace) => $this->traceToTraceTree($childTrace, $depth),
            $items
        );

        usort(
            $items,
            static function (TraceTreeObject $a, TraceTreeObject $b) {
                return $a->loggedAt->toDateTimeString('microsecond')
                    <=> $b->loggedAt->toDateTimeString('microsecond');
            }
        );

        return array_values($items);
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
