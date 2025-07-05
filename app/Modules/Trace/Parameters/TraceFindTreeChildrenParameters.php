<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters;

readonly class TraceFindTreeChildrenParameters
{
    public function __construct(
        public int $page,
        public bool $root,
        public string $traceId,
    ) {
    }
}
