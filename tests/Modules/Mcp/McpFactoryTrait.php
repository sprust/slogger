<?php

namespace Tests\Modules\Mcp;

use App\Modules\Mcp\Entities\McpObject;
use Illuminate\Support\Carbon;

trait McpFactoryTrait
{
    private function mcpObject(
        int $id = 1,
        bool $enabled = true,
        string $token = 'token',
        int $requestsCount = 0,
        ?Carbon $lastUsedAt = null
    ): McpObject {
        $now = Carbon::parse('2026-09-28 12:00:00');

        return new McpObject(
            id: $id,
            name: 'Claude Code',
            token: $token,
            enabled: $enabled,
            requestsCount: $requestsCount,
            lastUsedAt: $lastUsedAt,
            createdAt: $now,
            updatedAt: $now
        );
    }
}
