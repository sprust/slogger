<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters\Data;

readonly class TraceDataFilterBooleanParameters
{
    public function __construct(public bool $value)
    {
    }
}
