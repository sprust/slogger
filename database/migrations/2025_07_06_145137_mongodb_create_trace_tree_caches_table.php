<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

/**
 * The nodes of built trace trees, one document per node, kept for a day.
 */
return new class extends Migration {
    // Not Migration::$connection: the database manager has no Mongo driver registered.
    // This names a `database.connections.mongodb.*` entry, read below as plain config.
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'traceTreeCache';

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
                    'key'  => ['rootTraceId' => 1],
                    'name' => 'rootTraceId_1',
                ],
                [
                    'key'  => ['rootTraceId' => 1, 'traceId' => 1],
                    'name' => 'rootTraceId_1_traceId_1',
                ],
                [
                    'key'  => ['rootTraceId' => 1, 'depth' => 1, '_id' => 1],
                    'name' => 'rootTraceId_1_depth_1__id_1',
                ],
                [
                    // The children of a node in the order they are paged through.
                    'key'  => ['rootTraceId' => 1, 'parentTraceId' => 1, 'loggedAt' => 1, '_id' => 1],
                    'name' => 'rootTraceId_1_parentTraceId_1_loggedAt_1__id_1',
                ],
                [
                    'key'                => ['createdAt' => 1],
                    'name'               => 'createdAt_1',
                    'expireAfterSeconds' => 60 * 60 * 24,
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
