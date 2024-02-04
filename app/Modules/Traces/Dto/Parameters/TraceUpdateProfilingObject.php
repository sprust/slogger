<?php

namespace App\Modules\Traces\Dto\Parameters;

readonly class TraceUpdateProfilingObject
{
    public function __construct(
        public string $method,
        public TraceUpdateProfilingDataObject $data
    ) {
    }
}
