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
        'deleted_at' => 'datetime',
    ];
}
