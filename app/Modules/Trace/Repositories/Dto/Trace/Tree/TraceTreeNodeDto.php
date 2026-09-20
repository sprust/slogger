<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace\Tree;

use Illuminate\Support\Carbon;

/**
 * A trace as a node of a tree: the ten fields the cache keeps, and nothing else.
 *
 * TraceDto is the whole trace, `data` included, and building one parses that data into an
 * object tree. A tree build reads a thousand traces at a time and throws every one of
 * those trees away — the cache has no column for them. On the traces of this stand, which
 * carry 610 bytes of data each, that was 73 ms against 23 for the same thousand read
 * without it.
 */
readonly class TraceTreeNodeDto
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        public ?int $serviceId,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public string $status,
        public array $tags,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public Carbon $loggedAt,
    ) {
    }
}
