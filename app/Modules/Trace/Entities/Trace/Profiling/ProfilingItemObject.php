<?php

namespace App\Modules\Trace\Entities\Trace\Profiling;

class ProfilingItemObject
{
    /**
     * @param ProfilingItemDataObject[] $data
     */
    public function __construct(
        public string $id,
        public string $calling,
        public string $callable,
        public array $data
    ) {
    }
}
