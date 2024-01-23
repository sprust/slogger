<?php

namespace App\Modules\ProjectLogs\ProjectLogsRaw\Parameters;

use App\Modules\ProjectLogs\LogTypeEnum;
use Illuminate\Support\Carbon;

class CreateProjectRawLogParameters
{
    public function __construct(
        public string $service,
        public string $trackId,
        public ?string $parentTrackId,
        public LogTypeEnum $type,
        public array $tags,
        public array $data,
        public Carbon $loggedAt
    ) {
    }
}
