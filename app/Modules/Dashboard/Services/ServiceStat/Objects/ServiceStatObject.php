<?php

namespace App\Modules\Dashboard\Services\ServiceStat\Objects;

use Illuminate\Support\Carbon;

readonly class ServiceStatObject
{
    public function __construct(
        public ServiceStatServiceObject $service,
        public Carbon $from,
        public Carbon $to,
        public string $type,
        public string $status,
        public int $count
    ) {
    }
}
