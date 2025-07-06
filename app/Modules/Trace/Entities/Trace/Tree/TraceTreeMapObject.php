<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

class TraceTreeMapObject
{
    /**
     * @param TraceTreeMapObject[] $children
     */
    public function __construct(
        public readonly string $traceId,
        public array $children,
        public ?string $loggedAt,
    ) {
    }
}
