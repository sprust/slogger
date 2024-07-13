<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Profiling\Tree;

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
