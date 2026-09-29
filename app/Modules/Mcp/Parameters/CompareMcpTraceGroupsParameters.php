<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters;

use App\Modules\Mcp\Entities\McpTracePeriodObject;

readonly class CompareMcpTraceGroupsParameters
{
    /**
     * @param int[]    $serviceIds
     * @param string[] $groupAStatuses
     * @param string[] $groupBStatuses
     * @param string[] $types
     */
    public function __construct(
        public array $serviceIds,
        public McpTracePeriodObject $period,
        public array $groupAStatuses,
        public array $groupBStatuses,
        public string $by,
        public array $types = []
    ) {
    }
}
