<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Exceptions;

use Exception;

class McpTraceIndexBuildingException extends Exception
{
    public function __construct(
        public readonly string $indexId
    ) {
        parent::__construct("Trace dynamic index [$indexId] is being built");
    }
}
