<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

/**
 * One shape of trace behind an event: the traces of one service, type and set of tags.
 *
 * The same six fields whatever watcher produced it, which is why this is an object and
 * the numbers beside it are not: a group means the same thing for every type.
 *
 * `durationMax` and `slowestTraceId` are null for a watcher that counts rather than
 * times — only the slow-traces checker has a slowest trace to name.
 */
readonly class WatcherIncidentEventGroupObject
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        public int $serviceId,
        public string $type,
        public array $tags,
        public int $count,
        public ?float $durationMax,
        public ?string $slowestTraceId
    ) {
    }
}
