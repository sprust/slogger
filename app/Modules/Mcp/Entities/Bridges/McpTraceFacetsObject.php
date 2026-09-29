<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

readonly class McpTraceFacetsObject
{
    public function __construct(
        public McpTraceFacetListObject $types,
        public McpTraceFacetListObject $statuses,
        public McpTraceFacetListObject $tags
    ) {
    }
}
