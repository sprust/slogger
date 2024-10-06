<?php

namespace App\Modules\Trace\Repositories\Dto\Data;

readonly class TraceDataFilterItemDto
{
    public function __construct(
        public string $field,
        public ?bool $null = null,
        public ?TraceDataFilterNumericDto $numeric = null,
        public ?TraceDataFilterStringDto $string = null,
        public ?TraceDataFilterBooleanDto $boolean = null
    ) {
    }
}
