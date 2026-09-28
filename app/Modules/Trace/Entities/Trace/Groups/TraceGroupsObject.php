<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Groups;

readonly class TraceGroupsObject
{
    /**
     * @param TraceGroupObject[] $items
     */
    public function __construct(
        public array $items,
        public bool $truncated
    ) {
    }
}
