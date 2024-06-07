<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Timestamp;

class TraceTimestampFieldObject
{
    /**
     * @param TraceTimestampFieldIndicatorObject[] $indicators
     */
    public function __construct(
        public string $field,
        public array $indicators
    ) {
    }
}
