<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Exceptions;

use Exception;

class McpLogSourceNotFoundException extends Exception
{
    /**
     * @param string[] $sources
     */
    public function __construct(
        public readonly array $sources
    ) {
        parent::__construct('Unknown log source');
    }
}
