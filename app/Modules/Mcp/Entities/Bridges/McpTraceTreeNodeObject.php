<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

use App\Modules\Mcp\Entities\Trace\TraceServiceObject;
use App\Modules\Mcp\Entities\Trace\Tree\TraceTreeRawObject;

readonly class McpTraceTreeNodeObject
{
    public function __construct(
        public TraceTreeRawObject $node,
        public ?TraceServiceObject $service,
        public ?int $childrenCount
    ) {
    }
}
