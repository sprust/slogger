<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Groups;

use Illuminate\Support\Carbon;

readonly class TraceGroupObject
{
    public function __construct(
        public ?int $serviceId,
        public ?string $type,
        public ?string $status,
        public ?Carbon $startedAt,
        public int $count,
        public ?float $durationAvg,
        public ?float $durationP95,
        public ?float $durationMax,
        public ?string $exampleTraceId
    ) {
    }
}
