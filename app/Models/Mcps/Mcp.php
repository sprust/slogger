<?php

namespace App\Models\Mcps;

use App\Models\AbstractModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $token
 * @property bool        $enabled
 * @property int         $requests_count
 * @property Carbon|null $last_used_at
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 */
class Mcp extends AbstractModel
{
    use HasFactory;

    protected $casts = [
        'enabled'        => 'boolean',
        'requests_count' => 'integer',
        'last_used_at'   => 'datetime',
    ];
}
