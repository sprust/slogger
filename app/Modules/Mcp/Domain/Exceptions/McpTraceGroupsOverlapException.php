<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Exceptions;

use Exception;

class McpTraceGroupsOverlapException extends Exception
{
    /**
     * @param string[] $statuses
     */
    public function __construct(
        public readonly array $statuses
    ) {
        parent::__construct('Statuses in both groups: ' . implode(', ', $statuses));
    }
}
