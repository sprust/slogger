<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

class ProfilingItemObject
{
    /**
     * @param ProfilingItemDataObject[] $data
     * @param static[] $callables
     */
    public function __construct(
        public string $id,
        public string $call,
        public array $data,
        public array $callables,
    ) {
    }
}
