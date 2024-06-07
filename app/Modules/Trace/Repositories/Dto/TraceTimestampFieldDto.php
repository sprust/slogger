<?php

namespace App\Modules\Trace\Repositories\Dto;

class TraceTimestampFieldDto
{
    /**
     * @param TraceTimestampFieldIndicatorDto[] $indicators
     */
    public function __construct(
        public string $field,
        public array $indicators
    ) {
    }
}
