<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Data;

readonly class TraceDataAdditionalFieldObject
{
    public function __construct(
        public string $key,
        public array $values
    ) {
    }
}
