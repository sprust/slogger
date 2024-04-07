<?php

namespace App\Modules\TraceCollector\Domain\Entities\Parameters;

use Illuminate\Support\Carbon;

readonly class TraceTreeDeleteManyParameters
{
    public function __construct(
        public Carbon $to
    ) {
    }
}
