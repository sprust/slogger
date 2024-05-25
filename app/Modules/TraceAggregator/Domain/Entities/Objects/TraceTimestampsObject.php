<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

use Illuminate\Support\Carbon;

class TraceTimestampsObject
{
    public function __construct(
        public Carbon $timestamp,
        public Carbon $timestampTo,
        public int $count,
        public int $durationPercent
    ) {
    }
}
