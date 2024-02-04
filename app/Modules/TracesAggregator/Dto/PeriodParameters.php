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

    public static function fromStringValues(?string $from, ?string $to): ?static
    {
        $period = new static(
            from: $from,
            to: $to
        );

        if (!$period->from && !$period->to) {
            return null;
        }

        return $period;
    }
}
