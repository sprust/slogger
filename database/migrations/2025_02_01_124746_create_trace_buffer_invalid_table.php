<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

/**
 * Buffer documents the transporter could not act on, kept for 3 days with the reason.
 */
return new class extends Migration {
    // Not Migration::$connection: the database manager has no Mongo driver registered.
    // This names a `database.connections.mongodb.*` entry, read below as plain config.
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'invalidBuffer';

    // Nothing here is transactional, and the transaction the migrator would otherwise
    // open is on the default MySQL connection, which none of this touches.
    public $withinTransaction = false;

    public function up(): void
    {
        $database = $this->database();

        $database->command(['create' => $this->collectionName]);

        $database->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    // `iat`, not `cat`: the receiver stamps `iat` on everything it moves here, while `cat`
                    // is only on documents that came from the buffer whole — those that failed to decode
                    // carry their id, their raw bytes and the error, and nothing else.
                    'key'                => ['iat' => 1],
                    'name'               => 'iat_1',
                    'expireAfterSeconds' => 60 * 60 * 24 * 3,
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
