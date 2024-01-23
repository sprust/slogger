<?php

namespace App\Modules\ProjectLogs\RawLogs\Parameters;

use App\Modules\ProjectLogs\ProjectLogTypeEnum;
use Illuminate\Support\Carbon;

class ProjectRawLogCreateParameters
{
    public function __construct(
        public string $service,
        public string $trackId,
        public ?string $parentTrackId,
        public ProjectLogTypeEnum $type,
        public array $tags,
        public array $data,
        public Carbon $loggedAt
    ) {
    }
}
