<?php

namespace App\Modules\Trace\Entities\DynamicIndex;

readonly class TraceDynamicIndexStatsObject
{
    public function __construct(
        public int $inProcessCount,
        public int $errorsCount,
        public int $totalCount
    ) {
    }
}
