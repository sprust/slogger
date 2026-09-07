<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services;

use App\Modules\Watcher\Entities\WatcherTimelineBucketObject;
use App\Modules\Watcher\Entities\WatcherTimelineGroupObject;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use Illuminate\Support\Carbon;

/**
 * Reads windows out of a watcher's line.
 */
readonly class WatcherTimelineAnalyzer
{
    /**
     * How much of the recent line is not to be trusted yet.
     *
     * The receiver holds a bucket until it has been closed for fifteen seconds and writes
     * on a fifteen-second tick, so the last few buckets are still filling. Reading them as
     * if they were complete would show every window ending in a dip — which is exactly
     * what a spike watcher measures against and what a silence watcher would report.
     */
    public const int LAG_SECONDS = 60;

    /** The latest moment a window may reach. */
    public function windowEnd(Carbon $now): Carbon
    {
        return $now->clone()->subSeconds(self::LAG_SECONDS);
    }

    /**
     * The buckets starting in [$from, $to).
     *
     * @return WatcherTimelineBucketObject[]
     */
    public function bucketsIn(WatcherTimelineObject $timeline, Carbon $from, Carbon $to): array
    {
        return array_values(
            array_filter(
                $timeline->buckets,
                static fn(WatcherTimelineBucketObject $bucket): bool => $bucket->at->gte($from)
                    && $bucket->at->lt($to)
            )
        );
    }

    /** How many traces started in [$from, $to). */
    public function countIn(WatcherTimelineObject $timeline, Carbon $from, Carbon $to): int
    {
        $count = 0;

        foreach ($this->bucketsIn($timeline, $from, $to) as $bucket) {
            $count += $bucket->count;
        }

        return $count;
    }

    /**
     * The shapes seen in [$from, $to), each summed over the buckets it appears in and the
     * slowest trace of the window kept.
     *
     * @return WatcherTimelineGroupObject[]
     */
    public function groupsIn(WatcherTimelineObject $timeline, Carbon $from, Carbon $to): array
    {
        /** @var array<string, WatcherTimelineGroupObject> $groups */
        $groups = [];

        foreach ($this->bucketsIn($timeline, $from, $to) as $bucket) {
            foreach ($bucket->groups as $group) {
                $key = $this->key($group);

                $groups[$key] = isset($groups[$key])
                    ? $this->merge($groups[$key], $group)
                    : $group;
            }
        }

        return array_values($groups);
    }

    private function merge(
        WatcherTimelineGroupObject $first,
        WatcherTimelineGroupObject $second
    ): WatcherTimelineGroupObject {
        $slower = $second->durationMax > $first->durationMax ? $second : $first;

        return new WatcherTimelineGroupObject(
            serviceId: $first->serviceId,
            type: $first->type,
            tags: $first->tags,
            count: $first->count + $second->count,
            durationCount: $first->durationCount + $second->durationCount,
            durationSum: $first->durationSum + $second->durationSum,
            durationMax: $slower->durationMax,
            slowestTraceId: $slower->slowestTraceId ?? $first->slowestTraceId
        );
    }

    private function key(WatcherTimelineGroupObject $group): string
    {
        $tags = $group->tags;

        sort($tags);

        return $group->serviceId . "\0" . $group->type . "\0" . implode("\0", $tags);
    }
}
