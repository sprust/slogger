<?php

namespace App\Modules\Traces\Repository\Parameters;

use App\Modules\Traces\Enums\TraceTypeEnum;
use Illuminate\Support\Carbon;

class TraceCreateParameters
{
    public function __construct(
        public int $serviceId,
        public string $traceId,
        public ?string $parentTraceId,
        public TraceTypeEnum $type,
        public array $tags,
        public string $data,
        public Carbon $loggedAt
    ) {
    }
}
