<?php

namespace App\Modules\Trace\Entities\Trace\Data;

readonly class TraceDataAdditionalFieldObject
{
    public function __construct(
        public string $key,
        public array $values
    ) {
    }
}
