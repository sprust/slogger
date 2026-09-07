<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

/**
 * One tunable number of a watcher type, described well enough for a form to be built from
 * it.
 */
readonly class WatcherTypeFieldObject
{
    public function __construct(
        public string $key,
        public string $title,
        public string $valueType,
        public int|float $default
    ) {
    }
}
