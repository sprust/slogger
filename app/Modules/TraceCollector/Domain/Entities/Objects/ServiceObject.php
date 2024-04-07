<?php

namespace App\Modules\TraceCollector\Domain\Entities\Objects;

class ServiceObject
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }
}
