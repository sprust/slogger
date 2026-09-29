<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Exceptions;

use Exception;

class McpNotFoundException extends Exception
{
    public function __construct(int $mcpId)
    {
        parent::__construct("Mcp [$mcpId] not found");
    }
}
