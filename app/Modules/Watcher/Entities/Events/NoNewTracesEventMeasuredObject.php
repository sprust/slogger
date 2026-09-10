<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Events;

/** What the checker saw. */
readonly class NoNewTracesEventMeasuredObject
{
    public function __construct(
        public string $windowFrom,
        public string $windowTo
    ) {
    }
}
