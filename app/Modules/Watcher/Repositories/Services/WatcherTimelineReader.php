<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Repositories\Services;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Entities\WatcherTimelineBucketObject;
use App\Modules\Watcher\Entities\WatcherTimelineGroupObject;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use Illuminate\Support\Carbon;
use SConcur\Bson\UTCDateTime;

/**
 * Turns the stored line into objects, and puts right the one thing the receiver's writes
 * leave behind.
 *
 * Those writes are additive and never read the document first, which is what makes them
 * safe across restarts and across receivers. The cost is that a bucket reopened by a late
 * trace arrives as a second element with the same timestamp, and a bucket's rollup can
 * then hold the same shape twice. Both are added up here, so that everything downstream
 * sees one bucket per moment and one entry per shape.
 */
readonly class WatcherTimelineReader
{
    /**
     * @param array<int, mixed> $buckets
     */
    public function read(int $watcherId, array $buckets): WatcherTimelineObject
    {
        /** @var array<int, WatcherTimelineBucketObject> $byMoment */
        $byMoment = [];

        foreach ($buckets as $bucket) {
            if (!is_array($bucket)) {
                continue;
            }

            /** @var array<string, mixed> $bucket */
            $at = $this->readDate($bucket, 't');

            if (is_null($at)) {
                continue;
            }

            $key = $at->getTimestamp();

            $byMoment[$key] = isset($byMoment[$key])
                ? $this->mergeBuckets($byMoment[$key], $this->readBucket($at, $bucket))
                : $this->readBucket($at, $bucket);
        }

        ksort($byMoment);

        return new WatcherTimelineObject(
            watcherId: $watcherId,
            buckets: array_values($byMoment)
        );
    }

    /**
     * @param array<string, mixed> $bucket
     */
    private function readBucket(Carbon $at, array $bucket): WatcherTimelineBucketObject
    {
        $rawGroups = $bucket['g'] ?? [];

        /** @var array<string, WatcherTimelineGroupObject> $groups */
        $groups = [];

        if (is_array($rawGroups)) {
            foreach ($rawGroups as $rawGroup) {
                if (!is_array($rawGroup)) {
                    continue;
                }

                /** @var array<string, mixed> $rawGroup */
                $group = $this->readGroup($rawGroup);

                $key = $this->groupKey($group);

                $groups[$key] = isset($groups[$key])
                    ? $this->mergeGroups($groups[$key], $group)
                    : $group;
            }
        }

        return new WatcherTimelineBucketObject(
            at: $at,
            count: ArrayValueGetter::intNull($bucket, 'c') ?? 0,
            durationCount: ArrayValueGetter::intNull($bucket, 'dc') ?? 0,
            durationSum: ArrayValueGetter::floatNull($bucket, 'dSum') ?? 0,
            durationMax: ArrayValueGetter::floatNull($bucket, 'dMax') ?? 0,
            groups: array_values($groups)
        );
    }

    /**
     * @param array<string, mixed> $group
     */
    private function readGroup(array $group): WatcherTimelineGroupObject
    {
        $traceId = ArrayValueGetter::stringNull($group, 'tid');

        return new WatcherTimelineGroupObject(
            serviceId: ArrayValueGetter::intNull($group, 'sid') ?? 0,
            type: ArrayValueGetter::stringNull($group, 'tp') ?? '',
            tags: array_values(ArrayValueGetter::arrayStringNull($group, 'tgs') ?? []),
            count: ArrayValueGetter::intNull($group, 'c') ?? 0,
            durationCount: ArrayValueGetter::intNull($group, 'dc') ?? 0,
            durationSum: ArrayValueGetter::floatNull($group, 'dSum') ?? 0,
            durationMax: ArrayValueGetter::floatNull($group, 'dMax') ?? 0,
            // An empty string is the receiver's "nothing to point at": a group whose
            // traces have not finished has no slowest one yet.
            slowestTraceId: $traceId === '' ? null : $traceId
        );
    }

    private function mergeBuckets(
        WatcherTimelineBucketObject $first,
        WatcherTimelineBucketObject $second
    ): WatcherTimelineBucketObject {
        /** @var array<string, WatcherTimelineGroupObject> $groups */
        $groups = [];

        foreach ([...$first->groups, ...$second->groups] as $group) {
            $key = $this->groupKey($group);

            $groups[$key] = isset($groups[$key])
                ? $this->mergeGroups($groups[$key], $group)
                : $group;
        }

        return new WatcherTimelineBucketObject(
            at: $first->at,
            count: $first->count + $second->count,
            durationCount: $first->durationCount + $second->durationCount,
            durationSum: $first->durationSum + $second->durationSum,
            durationMax: max($first->durationMax, $second->durationMax),
            groups: array_values($groups)
        );
    }

    private function mergeGroups(
        WatcherTimelineGroupObject $first,
        WatcherTimelineGroupObject $second
    ): WatcherTimelineGroupObject {
        // The slowest trace of the two, so that merging never loses the one worth looking
        // at.
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

    private function groupKey(WatcherTimelineGroupObject $group): string
    {
        $tags = $group->tags;

        sort($tags);

        return $group->serviceId . "\0" . $group->type . "\0" . implode("\0", $tags);
    }

    /**
     * @param array<string, mixed> $document
     */
    private function readDate(array $document, string $key): ?Carbon
    {
        $value = $document[$key] ?? null;

        if ($value instanceof UTCDateTime) {
            return new Carbon($value->toDateTime());
        }

        return null;
    }
}
