<?php

namespace App\Models\Logs;

use App\Models\AbstractMongoModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * @property string $_id
 * @property string $level
 * @property string $message
 * @property array  $context
 * @property string $channel
 * @property Carbon $loggedAt
 * @property string $createdAt
 *
 * @method static Builder|Log query()
 */
class Log extends AbstractMongoModel
{
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = null;
    protected $connection   = 'mongodb.logs';

    protected $casts = [
        'loggedAt' => 'datetime',
    ];

    public function getCollectionName(): string
    {
        return config('database.connections.mongodb.logs.database');
    }
}
