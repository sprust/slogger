<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

/**
 * The receiver's queue of creates and updates, read by the transporter oldest first.
 */
return new class extends Migration {
    // Not Migration::$connection: the database manager has no Mongo driver registered.
    // This names a `database.connections.mongodb.*` entry, read below as plain config.
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'buffer';

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
                    // The order the transporter reads in, and a TTL: the buffer is a queue, not a store.
                    // A document still here six hours after it arrived is one no pass managed to save or
                    // to reject, and keeping it costs more than it is worth.
                    'key'                => ['cat' => 1],
                    'name'               => 'cat_1',
                    'expireAfterSeconds' => 60 * 60 * 6,
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
