<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters;

use App\Modules\Mcp\Entities\McpTracePeriodObject;

readonly class FindMcpTracesParameters
{
    /**
     * @param int[]    $serviceIds
     * @param string[] $types
     * @param string[] $statuses
     * @param string[] $tags
     * @param string[] $dataFilter
     * @param string[] $dataFields
     */
    public function __construct(
        public array $serviceIds,
        public McpTracePeriodObject $period,
        public array $types = [],
        public array $statuses = [],
        public array $tags = [],
        public ?float $durationFrom = null,
        public ?float $durationTo = null,
        public array $dataFilter = [],
        public array $dataFields = [],
        public int $page = 1
    ) {
    }
}
