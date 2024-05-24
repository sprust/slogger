<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

class TraceTimestampsObjects
{
    /**
     * @param TraceTimestampsObject[] $items
     */
    public function __construct(
        public array $items
    ) {
    }
}
