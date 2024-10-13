<?php

namespace App\Modules\Trace\Entities\Trace\Profiling;

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
