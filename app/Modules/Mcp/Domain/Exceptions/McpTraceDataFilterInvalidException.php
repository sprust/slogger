<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Exceptions;

use Exception;

class McpTraceDataFilterInvalidException extends Exception
{
    public function __construct(
        public readonly string $condition
    ) {
        parent::__construct("Invalid data filter condition [$condition]");
    }
}
