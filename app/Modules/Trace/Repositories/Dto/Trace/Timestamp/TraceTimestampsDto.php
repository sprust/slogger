<?php

declare(strict_types=1);

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
