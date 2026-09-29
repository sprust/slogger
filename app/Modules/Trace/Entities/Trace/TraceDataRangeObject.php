<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace;

use Illuminate\Support\Carbon;

readonly class TraceDataRangeObject
{
    public function __construct(
        public ?Carbon $firstHour,
        public ?Carbon $lastHour
    ) {
    }
}
