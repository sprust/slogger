<?php

namespace App\Models;

use App\Models\Concerns\HasSqlSconcurConnectionTrait;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractModel extends Model
{
    use HasSqlSconcurConnectionTrait;
}
