<?php

namespace App\Models\Traces;

use App\Models\AbstractModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $setting_id
 * @property int         $cleared_count
 * @property Carbon|null $cleared_at
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 */
class TraceClearingProcess extends AbstractModel
{
    protected $casts = [
        'cleared_at' => 'datetime',
    ];
}
