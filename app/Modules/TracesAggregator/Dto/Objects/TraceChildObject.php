<?php

namespace App\Modules\TracesAggregator\Dto\Objects;

use App\Modules\TracesAggregator\Dto\TraceObject;

readonly class TraceChildObject
{
    public function __construct(
        public TraceObject $child
    ) {
    }
}
