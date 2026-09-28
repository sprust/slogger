<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Exceptions;

use LogicException;

class McpServerNameInvalidException extends LogicException
{
    public function __construct(string $serverName)
    {
        parent::__construct(
            "MCP server name [$serverName] is invalid: set MCP_SERVER_NAME to 1-32 characters of a-z, 0-9 and '-'"
        );
    }
}
