<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters;

use App\Modules\Mcp\Entities\McpTracePeriodObject;

readonly class FindMcpTraceFacetsParameters
{
    /**
     * @param int[]    $serviceIds
     * @param string[] $types
     * @param string[] $statuses
     */
    public function __construct(
        public array $serviceIds,
        public McpTracePeriodObject $period,
        public array $types,
        public array $statuses,
        public int $limit
    ) {
    }
}
