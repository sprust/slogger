<?php

namespace App\Modules\Trace\Parameters;

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
            from: $from ? (new Carbon($from))->setTimezone('UTC') : null,
            to: $to ? (new Carbon($to))->setTimezone('UTC') : null,
        );

        if (!$period->from && !$period->to) {
            return null;
        }

        return $period;
    }
}
