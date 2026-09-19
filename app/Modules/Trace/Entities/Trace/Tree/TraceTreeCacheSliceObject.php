<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

readonly class TraceTreeCacheSliceObject
{
    public function __construct(
        public bool $stopped,
        public bool $finished,
        public ?int $nextDepth,
        public ?string $nextAfterId,
    ) {
    }
}
