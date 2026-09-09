<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

use Illuminate\Support\Carbon;

/**
 * Fifteen seconds of a watcher's line, as the receiver left it.
 */
readonly class WatcherTimelineBucketObject
{
    /**
     * @param WatcherTimelineGroupObject[] $groups
     */
    public function __construct(
        public Carbon $at,
        public int $count,
        public int $durationCount,
        public float $durationSum,
        public float $durationMax,
        public array $groups
    ) {
    }
}
