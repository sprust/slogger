<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters\Data;

readonly class TraceDataFilterItemParameters
{
    public function __construct(public string $field, public ?bool $null, public ?bool $exists, public ?TraceDataFilterNumericParameters $numeric, public ?TraceDataFilterStringParameters $string, public ?TraceDataFilterBooleanParameters $boolean)
    {
    }
}
