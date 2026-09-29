<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Entities\McpTracePeriodObject;

readonly class McpToolTraceScopeObject
{
    /**
     * @param int[] $serviceIds
     */
    public function __construct(
        public array $serviceIds,
        public McpTracePeriodObject $period
    ) {
    }
}
