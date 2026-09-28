<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Exceptions;

use Exception;

class McpTracePeriodTooWideException extends Exception
{
    public function __construct(
        public readonly int $maxHours
    ) {
        parent::__construct("The period is longer than $maxHours hours");
    }
}
