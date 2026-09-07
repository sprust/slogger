<?php

namespace App\Models\Watchers;

use App\Models\AbstractModel;
use Illuminate\Support\Carbon;

/**
 * @property int    $id
 * @property int    $incident_id
 * @property Carbon $occurred_at
 * @property array<string, mixed> $payload
 */
class WatcherIncidentEvent extends AbstractModel
{
    /** occurred_at is the only time this row has. */
    public $timestamps = false;

    protected $casts = [
        'occurred_at' => 'datetime',
        'payload'     => 'array',
    ];
}
