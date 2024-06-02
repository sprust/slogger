<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Profiling;

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
