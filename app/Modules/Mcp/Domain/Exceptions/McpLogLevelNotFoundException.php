<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Exceptions;

use Exception;

class McpLogLevelNotFoundException extends Exception
{
    /**
     * @param string[] $levels
     */
    public function __construct(
        public readonly string $level,
        public readonly array $levels
    ) {
        parent::__construct("Unknown log level [$level]");
    }
}
