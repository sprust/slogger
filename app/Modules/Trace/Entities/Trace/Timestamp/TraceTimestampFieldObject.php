<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Timestamp;

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
