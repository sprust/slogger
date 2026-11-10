<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Exceptions;

class McpTraceGroupsOverlapException extends AbstractMcpException
{
    /**
     * @param string[] $statuses
     */
    public function __construct(
        public readonly array $statuses
    ) {
        parent::__construct(
            'Statuses in both groups: '
                . implode(', ', $statuses)
        );
    }
}
