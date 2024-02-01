<?php

namespace App\Modules\TracesAggregator\Dto;

use Illuminate\Support\Carbon;

class PeriodParameters
{
    public function __construct(
        public ?Carbon $from = null,
        public ?Carbon $to = null
    ) {
    }
}
