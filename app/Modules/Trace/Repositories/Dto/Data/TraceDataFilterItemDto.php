<?php

namespace App\Modules\Trace\Repositories\Dto\Data;

readonly class TraceDataFilterItemDto
{
    public function __construct(
        public string $field,
        public ?bool $null,
        public ?TraceDataFilterNumericDto $numeric,
        public ?TraceDataFilterStringDto $string,
        public ?TraceDataFilterBooleanDto $boolean
    ) {
    }
}
