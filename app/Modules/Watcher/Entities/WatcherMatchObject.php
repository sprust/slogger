<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

/**
 * The filter as the Go receiver reads it — the whole of what that service knows about
 * watchers.
 *
 * It carries a version because the receiver has to be able to tell "a filter I do not
 * understand" from "a filter that matches nothing". On an unknown version it skips the
 * watcher and says so, rather than quietly counting the wrong traces.
 */
readonly class WatcherMatchObject
{
    public const int VERSION = 1;

    /**
     * @param int[]    $serviceIds
     * @param string[] $types
     * @param string[] $tags
     */
    public function __construct(
        public array $serviceIds = [],
        public array $types = [],
        public array $tags = [],
        public int $version = self::VERSION
    ) {
    }
}
