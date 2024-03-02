<?php

namespace App\Modules\TraceCollector\Dto\Parameters;

readonly class TraceUpdateProfilingObject
{
    public function __construct(
        public string $raw,
        public string $calling,
        public string $callable,
        public TraceUpdateProfilingDataObject $data
    ) {
    }
}
