<?php

namespace App\Models\Notifications;

use App\Models\AbstractModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $type
 * @property bool        $enabled
 * @property array<string, mixed> $settings
 * @property bool        $on_opened
 * @property bool        $on_event
 * @property bool        $on_closed
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 * @property Carbon|null $deleted_at
 */
class NotificationChannel extends AbstractModel
{
    use SoftDeletes;

    protected $casts = [
        'enabled'    => 'boolean',
        'settings'   => 'encrypted:array',
        'on_opened'  => 'boolean',
        'on_event'   => 'boolean',
        'on_closed'  => 'boolean',
        'deleted_at' => 'datetime',
    ];
}
