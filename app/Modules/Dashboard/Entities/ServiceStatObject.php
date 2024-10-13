<?php

namespace App\Modules\Dashboard\Entities;

use Illuminate\Support\Carbon;

readonly class ServiceStatObject
{
    public function __construct(
        public ServiceObject $service,
        public Carbon $from,
        public Carbon $to,
        public string $type,
        public string $status,
        public int $count
    ) {
    }
}
