<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

return new class extends Migration {
    // Not Migration::$connection: the database manager has no Mongo driver registered.
    // This names a `database.connections.mongodb.*` entry, read below as plain config.
    protected string $connectionName = 'mongodb.logs';
    protected string $collectionName = 'logs';

    // Nothing here is transactional, and the transaction the migrator would otherwise
    // open is on the default MySQL connection, which none of this touches.
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $database = $this->database();

        $database->command(['create' => $this->collectionName]);

        $secondsPerHour = 60 * 60;

        $database->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    'key'                => ['loggedAt' => 1],
                    'name'               => 'loggedAt_1',
                    'expireAfterSeconds' => $secondsPerHour * 36, // 3 days
                ],
                [
                    'key'  => [
                        'loggedAt' => 1,
                        'message'  => 'text',
                        'level'    => 1,
                    ],
                    'name' => 'loggedAt_1_message_text_level_1',
                ],
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $database = $this->database();

        $database->command([
            'dropIndexes' => $this->collectionName,
            'index'       => '*',
        ]);

        $database->command(['drop' => $this->collectionName]);
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
