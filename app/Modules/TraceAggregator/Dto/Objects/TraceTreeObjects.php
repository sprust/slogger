<?php

namespace App\Modules\TraceAggregator\Dto\Objects;

readonly class TraceTreeObjects
{
    /**
     * @param TraceTreeObject[] $items
     */
    public function __construct(
        public int $tracesCount,
        public array $items
    ) {
    }
}
