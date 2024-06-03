<?php

namespace App\Modules\Trace\Repositories\Dto\Data;

readonly class TraceDataFilterBooleanDto
{
    public function __construct(
        public bool $value
    ) {
    }
}
