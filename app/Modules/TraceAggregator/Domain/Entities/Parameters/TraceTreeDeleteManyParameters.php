<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Parameters;

use Illuminate\Support\Carbon;

readonly class TraceTreeDeleteManyParameters
{
    public function __construct(
        public Carbon $to
    ) {
    }
}
