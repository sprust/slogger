<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters\Data;

use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;

readonly class TraceDataFilterStringParameters
{
    public function __construct(
        public ?string $value,
        public TraceDataFilterCompStringTypeEnum $comp
    ) {
    }
}
