<?php

namespace App\Modules\Trace\Domain\Entities\Parameters;

readonly class TraceFindProfilingParameters
{
    public function __construct(
        public string $traceId,
    ) {
    }
}
