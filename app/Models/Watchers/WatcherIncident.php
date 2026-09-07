<?php

namespace App\Models\Watchers;

use App\Models\AbstractModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $watcher_id
 * @property string      $status
 * @property Carbon      $first_event_at
 * @property Carbon      $last_event_at
 * @property int         $events_count
 * @property Carbon|null $closed_at
 * @property int|null    $closed_by_user_id
 */
class WatcherIncident extends AbstractModel
{
    /** first_event_at and last_event_at say the same thing more precisely. */
    public $timestamps = false;

    protected $casts = [
        'first_event_at' => 'datetime',
        'last_event_at'  => 'datetime',
        'closed_at'      => 'datetime',
    ];
}
