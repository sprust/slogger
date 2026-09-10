<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Events;

/** What the checker saw. */
readonly class InvalidBufferGrownEventMeasuredObject
{
    public function __construct(
        public int $invalidCount,
        public string $since
    ) {
    }
}
