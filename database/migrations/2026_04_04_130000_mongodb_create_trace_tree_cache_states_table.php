<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

return new class extends Migration {
    // Not Migration::$connection: the database manager has no Mongo driver registered.
    // This names a `database.connections.mongodb.*` entry, read below as plain config.
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'traceTreeCacheStates';

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
                    'key'    => ['rootTraceId' => 1],
                    'name'   => 'rootTraceId_1',
                    'unique' => true,
                ],
                [
                    'key'                => ['updatedAt' => 1],
                    'name'               => 'updatedAt_1',
                    'expireAfterSeconds' => 60 * 60,
                ],
            ],
        ]);
    }

    public function down(): void
    {
        $this->database()->command(['drop' => $this->collectionName]);
    }

    /**
     * The connection, built here rather than taken from an application service.
     *
     * A migration has to keep meaning what it meant on the day it ran, and application
     * code moves on. What it may lean on is what does not: the configuration keys and the
     * driver. Index names are spelled out for the same reason — they are what the
     * collection actually carries, not what a helper would derive today.
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
