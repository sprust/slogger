<?php

namespace App\Modules\TraceCollector\Events;

readonly class TraceCreatedEvent
{
    public function __construct(
        public int $serviceId,
        public string $type,
        public string $status
    ) {
    }
}
