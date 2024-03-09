<?php

namespace App\Models\Traces;

use App\Models\AbstractModel;
use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\SoftDeletes;

/**
 * @property int         $id
 * @property int         $days_lifetime
 * @property string|null $type
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 */
class TraceClearingSetting extends AbstractModel
{
    use SoftDeletes;
}
