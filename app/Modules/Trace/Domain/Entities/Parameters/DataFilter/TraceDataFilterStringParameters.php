<?php

namespace App\Modules\Trace\Domain\Entities\Parameters\DataFilter;

use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;

readonly class TraceDataFilterStringParameters
{
    public function __construct(
        public ?string $value,
        public TraceDataFilterCompStringTypeEnum $comp
    ) {
    }
}
