<?php

namespace App\Modules\TraceAggregator\Dto\Objects;

readonly class TraceDataAdditionalFieldObject
{
    public function __construct(
        public string $key,
        public array $values
    ) {
    }
}
