<?php

namespace App\Modules\Trace\Domain\Entities\Parameters;

readonly class TraceFindTreeParameters
{
    public function __construct(
        public string $traceId
    ) {
    }
}
