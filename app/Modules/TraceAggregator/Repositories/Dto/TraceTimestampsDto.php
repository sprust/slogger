<?php

namespace App\Modules\TraceAggregator\Repositories\Dto;

use Illuminate\Support\Carbon;

class TraceTimestampsDto
{
    public function __construct(
        public Carbon $timestamp,
        public int $count
    ) {
    }
}
