<?php

namespace App\Models\Watchers;

use App\Models\AbstractTraceModel;
use Illuminate\Support\Carbon;

/**
 * One document per watcher, holding its line of 15-second buckets.
 *
 * Written by the Go receiver, read and trimmed by the panel. `_id` is the watcher's id,
 * which is why the collection carries no index of its own: every access addresses one
 * document by its key.
 *
 * @property int    $_id
 * @property list<array<string, mixed>> $tl
 * @property Carbon $uat
 */
class WatcherTimeline extends AbstractTraceModel
{
    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    public function getCollectionName(): string
    {
        return 'watcherTimelines';
    }
}
