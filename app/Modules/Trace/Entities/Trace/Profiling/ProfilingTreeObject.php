<?php

namespace App\Modules\Trace\Entities\Trace\Profiling;

readonly class ProfilingTreeObject
{
    /**
     * @param ProfilingTreeNodeObject[] $nodes
     */
    public function __construct(
        public array $nodes
    ) {
    }
}
