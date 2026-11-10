<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Exceptions;

class McpTracePeriodTooWideException extends AbstractMcpException
{
    public function __construct(public readonly int $maxHours)
    {
        parent::__construct("The period is longer than $maxHours hours");
    }
}
