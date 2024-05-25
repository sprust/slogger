<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

class TraceTimestampObject
{
    public function __construct(
        public string $value,
        public string $title
    ) {
    }
}
