<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Protocol;

use App\Modules\Mcp\Entities\McpSettingsObject;

readonly class McpInstructionsBuilder
{
    public function build(McpSettingsObject $settings): string
    {
        $installation = <<<TEXT
            This is the SLogger installation "$settings->serverName" at $settings->appUrl.
            Several SLogger installations (for example local, dev, stand, prod) may be
            connected at the same time as separate MCP servers. They do not share data:
            service ids, trace ids and incidents of one installation mean nothing in another.
            - Use the installation the user names. If the user does not name one and more
              than one is connected, ask which one before querying.
            - Never combine ids from different installations in one call.
            - In the answer, say which installation every finding comes from.
            TEXT;

        return $installation . "\n\n" . trim((string) file_get_contents(resource_path('mcp/instructions.md')));
    }
}
