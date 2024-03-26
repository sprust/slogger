<?php

namespace App\Modules\Dashboard\Services\ServiceStat\Objects;

readonly class ServiceStatServiceObject
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }
}
