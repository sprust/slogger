<?php

namespace App\Modules\TracesAggregator\Dto\Objects;

class TraceDataAdditionalFieldObject
{
    public function __construct(
        public string $key,
        public array $values
    ) {
    }
}
