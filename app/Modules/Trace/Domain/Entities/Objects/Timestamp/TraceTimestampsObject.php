<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Timestamp;

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
