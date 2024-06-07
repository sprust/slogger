<?php

namespace App\Modules\Trace\Repositories\Dto\Timestamp;

class TraceTimestampsListDto
{
    /**
     * @param TraceTimestampsDto[]     $timestamps
     * @param TraceTimestampFieldDto[] $emptyIndicators
     */
    public function __construct(
        public array $timestamps,
        public array $emptyIndicators
    ) {
    }
}
