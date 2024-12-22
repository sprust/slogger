<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\DynamicIndex;

use App\Modules\Trace\Entities\Trace\TraceIndexInfoObject;

readonly class TraceDynamicIndexStatsObject
{
    /**
     * @param TraceIndexInfoObject[] $indexesInProcess
     */
    public function __construct(
        public int $inProcessCount,
        public int $errorsCount,
        public int $totalCount,
        public array $indexesInProcess
    ) {
    }
}
