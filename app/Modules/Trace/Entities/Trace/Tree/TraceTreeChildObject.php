<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

use App\Modules\Trace\Entities\Trace\TraceServiceObject;
use Illuminate\Support\Carbon;

readonly class TraceTreeChildObject
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        public string $id,
        public ?TraceServiceObject $service,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public string $status,
        public array $tags,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public Carbon $loggedAt,
        public bool $hasChildren,
    ) {
    }
}
