<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Entities;

/**
 * Queue-consumer counters of a SConcur pool. Present only for groups whose workers
 * consume a broker queue; an HTTP pool reports requests instead and leaves this null.
 */
readonly class SconcurConsumersObject
{
    public function __construct(
        public int $coroutines,
        public int $delivered,
        public int $acked,
        public int $refused,
        public int $timed,
        public float $avgMs,
        public int $inFlight,
        public int $inFlight1to5s,
        public int $inFlight5to15s,
        public int $inFlightOver15s,
    ) {
    }
}
