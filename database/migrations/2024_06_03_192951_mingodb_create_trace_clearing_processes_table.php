<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

/**
 * The runs of the trace cleaner, shown on the Trace cleaner page, kept for 12 hours.
 */
return new class extends Migration {
    // Not Migration::$connection: the database manager has no Mongo driver registered.
    // This names a `database.connections.mongodb.*` entry, read below as plain config.
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'traceClearingProcesses';

    // Nothing here is transactional, and the transaction the migrator would otherwise
    // open is on the default MySQL connection, which none of this touches.
    public $withinTransaction = false;

    public function up(): void
    {
        $database = $this->database();

        $database->command([
            'create'    => $this->collectionName,
            'validator' => [
                '$jsonSchema' => [
                    'bsonType'   => 'object',
                    'required'   => [
                        'clearedCollectionsCount',
                        'clearedTracesCount',
                        'error',
                        'errorTrace',
                        'clearedAt',
                    ],
                    'properties' => [
                        'clearedCollectionsCount' => [
                            'bsonType' => 'number',
                        ],
                        'clearedTracesCount'      => [
                            'bsonType' => 'number',
                        ],
                        'error'                   => [
                            'bsonType' => ['string', 'null'],
                        ],
                        'errorTrace'              => [
                            'bsonType' => ['string', 'null'],
                        ],
                        'clearedAt'               => [
                            'bsonType' => ['date', 'null'],
                        ],
                        'createdAt'               => [
                            'bsonType' => 'date',
                        ],
                        'updatedAt'               => [
                            'bsonType' => 'date',
                        ],
                    ],
                ],
            ],
        ]);

        $database->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    'key'                => ['createdAt' => 1],
                    'name'               => 'createdAt_1',
                    'expireAfterSeconds' => 60 * 60 * 12,
                ],
            ],
        ]);
    }

    public function down(): void
    {
        $this->database()->command(['drop' => $this->collectionName]);
    }

    /**
     * The connection, built here rather than taken from an application service: a
     * migration has to keep meaning what it meant on the day it ran.
     */
    private function database(): Database
    {
        $config = config("database.connections.$this->connectionName");

        return new Client(
            "mongodb://{$config['username']}:{$config['password']}@{$config['host']}:{$config['port']}",
            timeoutMs: $config['options']['socketTimeoutMS'] ?? null,
        )->selectDatabase($config['database']);
    }
};
