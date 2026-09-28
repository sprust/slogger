<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Queries;

use App\Modules\Mcp\Domain\Exceptions\McpServerNameInvalidException;
use App\Modules\Mcp\Entities\McpSettingsObject;

readonly class FindMcpSettingsAction
{
    private const string SERVER_NAME_PATTERN = '/^[a-z0-9-]{1,32}$/';

    /**
     * @throws McpServerNameInvalidException
     */
    public function handle(): McpSettingsObject
    {
        $serverName = (string) config('mcp.server_name');

        if (preg_match(self::SERVER_NAME_PATTERN, $serverName) !== 1) {
            throw new McpServerNameInvalidException($serverName);
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        return new McpSettingsObject(
            serverName: $serverName,
            serverVersion: (string) config('mcp.server_version'),
            appUrl: $appUrl,
            endpointUrl: "$appUrl/mcp",
            maxStringLength: (int) config('mcp.max_string_length'),
            treeNodesLimit: (int) config('mcp.tree_nodes_limit'),
            listTtlMs: (int) config('mcp.list_ttl_ms')
        );
    }
}
