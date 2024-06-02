<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects\Data;

readonly class TraceDataAdditionalFieldObject
{
    public function __construct(
        public string $key,
        public array $values
    ) {
    }
}
