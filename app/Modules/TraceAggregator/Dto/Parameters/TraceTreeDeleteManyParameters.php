<?php

namespace App\Modules\TraceAggregator\Dto\Parameters;

use Illuminate\Support\Carbon;

readonly class TraceTreeDeleteManyParameters
{
    public function __construct(
        public Carbon $to
    ) {
    }
}
