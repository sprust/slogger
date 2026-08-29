<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $first_name
 * @property string|null $last_name
 * @property string      $email
 * @property Carbon|null $email_verified_at
 * @property string      $password
 * @property string      $api_token
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 */
class User extends Authenticatable
{
    use HasFactory;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];
}
