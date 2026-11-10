<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Exceptions;

class McpTraceDataFilterInvalidException extends AbstractMcpException
{
    public function __construct(
        public readonly string $condition
    ) {
        parent::__construct("Invalid data filter condition [$condition]");
    }
}
