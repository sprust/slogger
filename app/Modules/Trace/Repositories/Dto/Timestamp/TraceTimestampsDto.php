<?php

namespace App\Modules\Trace\Repositories\Dto\Timestamp;

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
