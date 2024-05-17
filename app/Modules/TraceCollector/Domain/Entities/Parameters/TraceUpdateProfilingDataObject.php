<?php

namespace App\Modules\TraceCollector\Domain\Entities\Parameters;

readonly class TraceUpdateProfilingDataObject
{
    public function __construct(
        public string $name,
        public int|float $value,
    ) {
    }
}
