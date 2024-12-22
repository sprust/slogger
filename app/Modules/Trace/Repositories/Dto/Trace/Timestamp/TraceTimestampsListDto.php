<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace\Timestamp;

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
