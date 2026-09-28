<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities;

readonly class McpSettingsObject
{
    public function __construct(
        public string $serverName,
        public string $serverVersion,
        public string $appUrl,
        public string $endpointUrl,
        public int $maxStringLength,
        public int $treeNodesLimit,
        public int $facetsLimit,
        public int $listTtlMs
    ) {
    }
}
