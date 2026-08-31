<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Entities;

/**
 * What a SConcur pool did with the work handed to PHP, whatever shape that work has.
 *
 * The panel keeps two sections that never appear together on one pool: an HTTP pool
 * reports `requests`, a queue-consumer pool reports `consumers`, and the task pool
 * reports its ticks in the consumer section (a tick is to a task what a delivery is to a
 * consumer). Split, the dashboard carried two columns for every number, each of them a
 * dash for half the rows. They are folded into one section here because the two count
 * the same thing:
 *
 * - `inProcess` — `requests.inFlight` ("in handling right now") and `consumers.inFlight`
 *   ("handed over and not yet settled") are the same quantity under two names.
 * - `finished` — `requests.completed` counts every request that ended, a failed one
 *   included, so its counterpart is `acked + refused` rather than `acked` alone.
 *   `refused` stays beside it as the share of that total which failed.
 * - `avgMs` — both are the time one unit of work spent in the handler, so they are
 *   averaged together weighted by the counts each of them is a mean over: `completed`
 *   for requests, `timed` for consumers. That sum is `measured`, which is not `finished`:
 *   an auto-acked delivery is finished with no duration to measure.
 *
 * Null rather than zeroes for a pool that reports neither section: a pool that did no
 * work and a pool that counts none are not the same, and the panel distinguishes them by
 * showing a dash.
 */
readonly class SconcurWorkObject
{
    public function __construct(
        public int $inProcess,
        public int $inProcess1to5s,
        public int $inProcess5to15s,
        public int $inProcessOver15s,
        public int $finished,
        public int $refused,
        public int $measured,
        public float $avgMs,
    ) {
    }
}
