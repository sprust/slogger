<?php

namespace App\Modules\TracesAggregator\Dto\Objects;

class TraceDataCustomFieldObject
{
    public function __construct(
        public string $key,
        public array $values
    ) {
    }
}
