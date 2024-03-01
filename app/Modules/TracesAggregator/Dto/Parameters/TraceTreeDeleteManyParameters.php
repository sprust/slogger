<?php

namespace App\Modules\TracesAggregator\Dto\Parameters;

use Illuminate\Support\Carbon;

readonly class TraceTreeDeleteManyParameters
{
    public function __construct(
        public Carbon $to
    ) {
    }
}
