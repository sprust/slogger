<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

/**
 * One shape of trace inside a bucket: the traces of one service, type and set of tags,
 * with the slowest of them named.
 *
 * `count` is traces started; `durationCount` is how many of them have finished, which is
 * not the same number and is why an average is `durationSum / durationCount`.
 */
readonly class WatcherTimelineGroupObject
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        public int $serviceId,
        public string $type,
        public array $tags,
        public int $count,
        public int $durationCount,
        public float $durationSum,
        public float $durationMax,
        public ?string $slowestTraceId
    ) {
    }
}
