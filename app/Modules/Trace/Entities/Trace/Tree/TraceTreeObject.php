<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

use App\Modules\Trace\Entities\Trace\TraceServiceObject;
use Illuminate\Support\Carbon;

readonly class TraceTreeObject
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        public ?TraceServiceObject $service,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public array $tags,
        public string $status,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public Carbon $loggedAt,
        public int $depth,
    ) {
    }
}
