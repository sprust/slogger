<?php

namespace App\Modules\Trace\Repositories\Dto;

readonly class TraceDynamicIndexStatsDto
{
    public function __construct(
        public int $inProcessCount,
        public int $errorsCount,
        public int $totalCount
    ) {
    }
}
