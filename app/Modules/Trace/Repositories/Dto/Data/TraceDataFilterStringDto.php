<?php

namespace App\Modules\Trace\Repositories\Dto\Data;

use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;

readonly class TraceDataFilterStringDto
{
    public function __construct(
        public ?string $value,
        public TraceDataFilterCompStringTypeEnum $comp
    ) {
    }
}
