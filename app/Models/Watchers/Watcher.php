<?php

namespace App\Models\Watchers;

use App\Models\AbstractModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $type
 * @property bool        $enabled
 * @property array<string, mixed>|null $trace_match
 * @property array<string, mixed>      $settings
 * @property int         $cooldown_seconds
 * @property int|null    $notification_channel_id
 * @property Carbon|null $collect_since
 * @property Carbon|null $last_checked_at
 * @property Carbon|null $last_triggered_at
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 * @property Carbon|null $deleted_at
 */
class Watcher extends AbstractModel
{
    use SoftDeletes;

    protected $casts = [
        'enabled'           => 'boolean',
        'trace_match'       => 'array',
        'settings'          => 'array',
        'collect_since'     => 'datetime',
        'last_checked_at'   => 'datetime',
        'last_triggered_at' => 'datetime',
        'deleted_at'        => 'datetime',
    ];
}
