<?php

namespace App\Modules\Dashboard\Entities;

use Illuminate\Support\Carbon;

readonly class ServiceStatRawObject
{
    public function __construct(
        public int $serviceId,
        public Carbon $from,
        public Carbon $to,
        public string $type,
        public string $status,
        public int $count
    ) {
    }
}
