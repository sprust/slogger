<?php

namespace App\Modules\Traces\Raw\Parameters;

use App\Modules\Traces\TraceTypeEnum;
use Illuminate\Support\Carbon;

class TraceCreateParameters
{
    public function __construct(
        public string $service,
        public string $trackId,
        public ?string $parentTrackId,
        public TraceTypeEnum $type,
        public array $tags,
        public array $data,
        public Carbon $loggedAt
    ) {
    }
}
