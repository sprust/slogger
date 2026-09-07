<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

use Illuminate\Support\Carbon;

/**
 * What one pass over the watchers reads once and shares.
 *
 * The clock is here so that every watcher of a pass judges the same moment; the buffer
 * size, because it is the same number for all of them and reading it per watcher would
 * ask the same question of Mongo as many times as there are buffer watchers.
 *
 * The size is nullable, and null means "could not be read". A watcher that judges it then
 * says nothing, which is the honest answer: the alternative is passing zero and reporting
 * an empty buffer to somebody watching for a full one.
 */
readonly class WatcherCheckContextObject
{
    public function __construct(
        public Carbon $now,
        public ?int $bufferCount
    ) {
    }
}
