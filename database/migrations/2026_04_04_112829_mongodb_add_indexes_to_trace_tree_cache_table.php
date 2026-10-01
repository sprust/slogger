<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

return new class extends Migration {
    // Not Migration::$connection: the database manager has no Mongo driver registered.
    // This names a `database.connections.mongodb.*` entry, read below as plain config.
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'traceTreeCache';

    // Nothing here is transactional, and the transaction the migrator would otherwise
    // open is on the default MySQL connection, which none of this touches.
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $database = $this->database();

        // Already created by an earlier migration; repeating it with the same (absent)
        // options is what the server lets through, and keeps this one standalone.
        $database->command(['create' => $this->collectionName]);

        $database->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    'key'  => [
                        'rootTraceId' => 1,
                        'traceId'     => 1,
                    ],
                    'name' => 'rootTraceId_1_traceId_1',
                ],
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->database()->command([
            'dropIndexes' => $this->collectionName,
            'index'       => 'rootTraceId_1_traceId_1',
        ]);
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
