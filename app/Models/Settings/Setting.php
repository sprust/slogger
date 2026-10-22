<?php

namespace App\Models\Settings;

use App\Models\AbstractModel;
use Illuminate\Support\Carbon;

/**
 * @property int    $id
 * @property string $key
 * @property string $value
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Setting extends AbstractModel
{
}
