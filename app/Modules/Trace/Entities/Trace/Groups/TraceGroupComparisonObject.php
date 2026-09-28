<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Groups;

readonly class TraceGroupComparisonObject
{
    /**
     * @param TraceGroupComparisonRowObject[] $rows
     */
    public function __construct(
        public int $groupATotal,
        public int $groupBTotal,
        public array $rows,
        public bool $truncated
    ) {
    }
}
