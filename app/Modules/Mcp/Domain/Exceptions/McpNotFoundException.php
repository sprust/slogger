<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Exceptions;

class McpNotFoundException extends AbstractMcpException
{
    public function __construct(int $mcpId)
    {
        parent::__construct("Mcp [$mcpId] not found");
    }
}
