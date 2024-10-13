<?php

namespace App\Modules\Trace\Repositories\Dto\Trace\Timestamp;

use Illuminate\Support\Carbon;

class TraceTimestampsDto
{
    /**
     * @param TraceTimestampFieldDto[] $indicators
     */
    public function __construct(
        public Carbon $timestamp,
        public array $indicators
    ) {
    }
}
