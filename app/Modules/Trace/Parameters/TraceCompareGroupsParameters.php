<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters;

use App\Modules\Trace\Enums\TraceCompareByEnum;

readonly class TraceCompareGroupsParameters
{
    /**
     * @param string[] $groupAStatuses
     * @param string[] $groupBStatuses
     * @param int[]    $serviceIds
     * @param string[] $types
     */
    public function __construct(
        public PeriodParameters $loggingPeriod,
        public array $groupAStatuses,
        public array $groupBStatuses,
        public TraceCompareByEnum $by,
        public ?string $dataKey,
        public int $limit,
        public array $serviceIds = [],
        public array $types = []
    ) {
    }
}
