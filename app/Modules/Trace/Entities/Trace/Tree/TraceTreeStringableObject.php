<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

readonly class TraceTreeStringableObject
{
    public function __construct(
        public string $name,
        public int $tracesCount,
    ) {
    }
}
