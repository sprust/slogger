<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

class ProfilingObject
{
    /**
     * @param ProfilingItemObject[] $items
     */
    public function __construct(
        public string $mainCaller,
        public array $items
    ) {
    }
}
