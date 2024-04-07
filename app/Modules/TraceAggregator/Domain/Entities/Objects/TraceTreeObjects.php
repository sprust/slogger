<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

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
