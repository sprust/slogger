<?php

namespace App\Modules\Traces\Repository\Parameters;

use App\Modules\Traces\TraceTypeEnum;
use Illuminate\Support\Carbon;

class TraceCreateParameters
{
    public function __construct(
        public int $serviceId,
        public string $traceId,
        public ?string $parentTraceId,
        public TraceTypeEnum $type,
        public array $tags,
        public array $data,
        public Carbon $loggedAt
    ) {
    }
}
