<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters;

readonly class TraceFindTreeParameters
{
    public function __construct(
        public string $traceId
    ) {
    }
}
