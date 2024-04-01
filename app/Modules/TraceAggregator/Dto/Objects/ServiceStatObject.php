<?php

namespace App\Modules\TraceAggregator\Dto\Objects;

readonly class ServiceStatObject
{
    public function __construct(
        public ?TraceServiceObject $service,
        public string $type,
        public string $tag,
        public string $status,
        public int $count
    ) {
    }
}
