<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Exceptions;

class McpLogSourceNotFoundException extends AbstractMcpException
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
