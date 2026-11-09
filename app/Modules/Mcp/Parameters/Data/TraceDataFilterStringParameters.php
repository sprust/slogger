<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters\Data;

use App\Modules\Mcp\Enums\TraceDataFilterCompStringTypeEnum;

readonly class TraceDataFilterStringParameters
{
    public function __construct(
        public ?string $value,
        public TraceDataFilterCompStringTypeEnum $comp
    ) {
    }
}
