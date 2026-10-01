<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

/**
 * Drops the collections nothing reads since the traces moved to ClickHouse. `tracesPeriodic`
 * is left to bin/traces-migrate, which moves it to ClickHouse and drops it as it goes.
 */
return new class extends Migration {
    // Not Migration::$connection: the database manager has no Mongo driver registered.
    // This names a `database.connections.mongodb.*` entry, read below as plain config.
    protected string $connectionName = 'mongodb.traces';

    // Nothing here is transactional, and the transaction the migrator would otherwise
    // open is on the default MySQL connection, which none of this touches.
    public $withinTransaction = false;

    private const array COLLECTIONS = ['traceDynamicIndexes', 'bufferInvalid'];

    public function up(): void
    {
        $database = $this->database();
        $existing = $database->listCollections();

        foreach (self::COLLECTIONS as $collectionName) {
            if (in_array($collectionName, $existing, true)) {
                $database->command(['drop' => $collectionName]);
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('The dropped collections and their data cannot be brought back.');
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
