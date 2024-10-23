<?php

namespace App\Modules\Trace\Repositories\Dto\Trace;

readonly class TraceDynamicIndexStatsDto
{
    public function __construct(
        public int $inProcessCount,
        public int $errorsCount,
        public int $totalCount
    ) {
    }
}
