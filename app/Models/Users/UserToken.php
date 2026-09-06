<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One signed-in session, addressed by the token the panel holds.
 *
 * The token itself is not here: only its sha256, so that a copy of this table is not a
 * set of working keys. Lookups hash the presented token and compare.
 *
 * @property int         $id
 * @property int         $user_id
 * @property string      $token_hash
 * @property Carbon      $created_at
 * @property Carbon      $last_used_at
 * @property Carbon      $expires_at
 */
class UserToken extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token_hash',
        'created_at',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'created_at'   => 'datetime',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];
}
