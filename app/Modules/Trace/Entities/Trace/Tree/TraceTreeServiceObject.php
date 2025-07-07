<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

readonly class TraceTreeServiceObject
{
    public function __construct(
        public int $id,
        public string $name,
        public int $tracesCount,
    ) {
    }
}
