<?php

namespace App\Modules\Trace\Entities\Trace\Profiling;

readonly class ProfilingTreeNodeObject
{
    /**
     * @param ProfilingItemDataObject[] $data
     */
    public function __construct(
        public int $id,
        public string $calling,
        public array $data,
        public ?int $recursionNodeId = null,
        public ?array $children = null
    ) {
    }
}
