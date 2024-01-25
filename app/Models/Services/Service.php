<?php

namespace App\Models\Services;

use App\Models\AbstractModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property int    $id
 * @property string $name
 * @property string $unique_key
 * @property string $api_token
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Service extends AbstractModel
{
    use HasFactory;
}
