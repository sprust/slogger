<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters\Data;

use App\Modules\Mcp\Enums\TraceDataFilterCompNumericTypeEnum;

readonly class TraceDataFilterNumericParameters
{
    public function __construct(public int|float $value, public TraceDataFilterCompNumericTypeEnum $comp)
    {
    }
}
