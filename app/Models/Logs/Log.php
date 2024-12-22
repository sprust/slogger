<?php

namespace App\Models\Logs;

use App\Models\AbstractMongoModel;

/**
 * @property string $_id
 * @property string $level
 * @property string $message
 * @property array  $context
 * @property string $channel
 * @property string $loggedAt
 * @property string $createdAt
 */
class Log extends AbstractMongoModel
{
    protected $connection = 'mongodb.logs';

    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = null;

    protected $casts = [
        'loggedAt' => 'datetime',
    ];

    function getCollectionName(): string
    {
        return config('database.connections.mongodb.logs.database');
    }
}
