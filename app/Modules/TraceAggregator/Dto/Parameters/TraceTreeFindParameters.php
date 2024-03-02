<?php

namespace App\Modules\TraceAggregator\Dto\Parameters;

use Illuminate\Support\Carbon;

readonly class TraceTreeFindParameters
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
        public ?Carbon $to = null,
    ) {
    }
}
