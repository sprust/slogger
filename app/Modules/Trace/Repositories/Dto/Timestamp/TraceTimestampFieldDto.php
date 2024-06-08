<?php

namespace App\Modules\Trace\Repositories\Dto\Timestamp;

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
