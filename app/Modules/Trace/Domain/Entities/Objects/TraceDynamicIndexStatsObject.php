<?php

namespace App\Modules\Trace\Domain\Entities\Objects;

readonly class TraceDynamicIndexStatsObject
{
    public function __construct(
        public int $inProcessCount,
        public int $errorsCount,
        public int $totalCount
    ) {
    }
}
