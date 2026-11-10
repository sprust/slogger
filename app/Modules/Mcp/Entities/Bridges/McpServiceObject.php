<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

readonly class McpServiceObject
{
    public function __construct(public int $id, public string $name)
    {
    }
}
