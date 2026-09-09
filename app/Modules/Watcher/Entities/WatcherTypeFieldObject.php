<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

/**
 * One tunable number of a watcher type, described well enough for a form to be built from
 * it.
 */
readonly class WatcherTypeFieldObject
{
    /**
     * @param int|float      $min what the server accepts at the low end
     * @param int|float|null $max and at the high end, where there is one
     */
    public function __construct(
        public string $key,
        public string $title,
        public string $valueType,
        public int|float $default,
        public int|float $min = 1,
        public int|float|null $max = null
    ) {
    }
}
