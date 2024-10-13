<?php

namespace App\Modules\Trace\Repositories\Dto\Trace\Timestamp;

class TraceTimestampFieldMapsDto
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
