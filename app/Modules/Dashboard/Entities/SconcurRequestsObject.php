<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Entities;

/**
 * Request counters of a SConcur pool. Present only for groups whose workers serve
 * requests; a queue-consumer pool reports deliveries instead and leaves this null.
 *
 * Null rather than zeroes, because the two say different things: a pool that served no
 * requests yet and a pool that never will are not the same, and the panel distinguishes
 * them by omitting the section entirely.
 */
readonly class SconcurRequestsObject
{
    public function __construct(
        public int $completed,
        public float $avgMs,
        public int $inFlight,
        public int $inFlight1to5s,
        public int $inFlight5to15s,
        public int $inFlightOver15s,
    ) {
    }
}
