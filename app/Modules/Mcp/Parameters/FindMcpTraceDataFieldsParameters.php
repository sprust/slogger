<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters;

use App\Modules\Mcp\Entities\McpTracePeriodObject;

readonly class FindMcpTraceDataFieldsParameters
{
    /**
     * @param int[] $serviceIds
     */
    public function __construct(
        public array $serviceIds,
        public McpTracePeriodObject $period,
        public string $type
    ) {
    }
}
