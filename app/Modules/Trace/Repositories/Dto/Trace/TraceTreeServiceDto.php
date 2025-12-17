<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace;

readonly class TraceTreeServiceDto
{
    public function __construct(
        public int $id,
        public int $tracesCount,
    ) {
    }
}
