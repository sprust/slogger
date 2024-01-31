<?php

namespace App\Modules\Traces\Dto\Parameters;

class TraceUpdateParameters
{
    public function __construct(
        public int $serviceId,
        public string $traceId,
        public ?array $tags,
        public ?string $data
    ) {
    }
}
