<?php

namespace App\Models\Watchers;

use App\Models\AbstractTraceModel;
use Illuminate\Support\Carbon;

/**
 * One incident: a watcher had something to say, and it stands until a person closes it.
 *
 * In Mongo rather than beside the watcher in MySQL. Everything a watcher produces is
 * periodic — it accumulates while the system runs and is worth keeping for a while, not
 * for ever — so it lives where a TTL index can retire it, on `lastEventAt`. The settings
 * are the only part of a watcher that has to be kept exactly as long as the watcher does.
 *
 * Because a TTL retires these on its own, deleting a watcher leaves its incidents alone:
 * there is no foreign key to cascade and no sweep to run.
 *
 * @property string      $_id
 * @property int         $watcherId
 * @property string      $status
 * @property Carbon      $firstEventAt
 * @property Carbon      $lastEventAt
 * @property int         $eventsCount
 * @property Carbon|null $closedAt
 * @property int|null    $closedByUserId
 */
class WatcherIncident extends AbstractTraceModel
{
    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    public function getCollectionName(): string
    {
        return 'watcherIncidents';
    }
}
