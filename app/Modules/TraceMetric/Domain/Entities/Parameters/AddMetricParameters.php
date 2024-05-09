<?php

namespace App\Modules\TraceMetric\Domain\Entities\Parameters;

readonly class AddMetricParameters
{
    public function __construct(
        public int $serviceId,
        public string $type,
        public string $status
    ) {
    }
}
