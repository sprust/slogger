<?php

namespace App\Modules\Trace\Entities\Trace\Timestamp;

class TraceTimestampObject
{
    public function __construct(
        public string $value,
        public string $title
    ) {
    }
}
