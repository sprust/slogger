<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Repositories;

use App\Models\Watchers\WatcherTimeline;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use App\Modules\Watcher\Repositories\Services\WatcherTimelineReader;
use Illuminate\Support\Carbon;
use SConcur\Bson\UTCDateTime;

/**
 * One watcher's line, addressed by its id.
 *
 * Nothing here writes buckets: those come from the Go receiver, which is the only thing
 * that sees traces. The panel reads the line, trims what has scrolled out of view, and
 * removes lines whose watcher is gone.
 */
readonly class WatcherTimelineRepository
{
    public function __construct(
        private WatcherTimelineReader $reader
    ) {
    }

    public function find(int $watcherId): WatcherTimelineObject
    {
        $document = WatcherTimeline::sconcur()->findOne(['_id' => $watcherId]);

        $buckets = $document['tl'] ?? [];

        return $this->reader->read(
            watcherId: $watcherId,
            buckets: is_array($buckets) ? array_values($buckets) : []
        );
    }

    /** Drops the buckets that start before the given moment. */
    public function trim(int $watcherId, Carbon $before): void
    {
        WatcherTimeline::sconcur()->updateOne(
            filter: ['_id' => $watcherId],
            update: [
                '$pull' => [
                    'tl' => [
                        't' => ['$lt' => new UTCDateTime($before)],
                    ],
                ],
            ],
        );
    }

    public function delete(int $watcherId): void
    {
        WatcherTimeline::sconcur()->deleteOne(['_id' => $watcherId]);
    }
}
