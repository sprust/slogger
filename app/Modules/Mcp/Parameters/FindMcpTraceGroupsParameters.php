<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters;

use App\Modules\Mcp\Entities\McpTracePeriodObject;

readonly class FindMcpTraceGroupsParameters
{
    /**
     * @param int[]    $serviceIds
     * @param string[] $groupBy
     * @param string[] $types
     * @param string[] $tags
     * @param string[] $statuses
     */
    public function __construct(
        public array $serviceIds,
        public McpTracePeriodObject $period,
        public array $groupBy,
        public array $types = [],
        public array $tags = [],
        public array $statuses = [],
        public ?float $durationFrom = null,
        public ?float $durationTo = null
    ) {
    }
}
