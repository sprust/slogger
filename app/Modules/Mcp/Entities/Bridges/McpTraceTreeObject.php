<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;

readonly class McpTraceTreeObject
{
    /**
     * @param McpTraceTreeNodeObject[] $nodes
     */
    public function __construct(
        public string $rootTraceId,
        public ?TraceTreeCacheStateStatusEnum $status,
        public ?string $error,
        public array $nodes,
        public ?string $nextCursor,
        public int $matchedCount,
        public bool $truncated
    ) {
    }
}
