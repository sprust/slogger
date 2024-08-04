<?php

namespace App\Models\Traces;

use App\Models\AbstractModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $type
 * @property int         $days_lifetime
 * @property boolean     $only_data
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 * @property Carbon|null $deleted_at
 */
class TraceClearingSetting extends AbstractModel
{
    protected $casts = [
        'only_data' => 'boolean',
    ];
}
