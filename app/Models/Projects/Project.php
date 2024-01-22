<?php

namespace App\Models\Projects;

use App\Models\AbstractModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property int    $id
 * @property int    $user_id
 * @property string $name
 * @property string $database_name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Project extends AbstractModel
{
    use HasFactory;
}
