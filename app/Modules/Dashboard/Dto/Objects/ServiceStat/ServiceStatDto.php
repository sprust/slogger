<?php

namespace App\Modules\Dashboard\Dto\Objects\ServiceStat;

use Illuminate\Support\Carbon;

readonly class ServiceStatDto
{
    public function __construct(
        public Carbon $from,
        public Carbon $to,
        public int $serviceId,
        public string $type,
        public string $status,
        public int $count
    ) {
    }
}
