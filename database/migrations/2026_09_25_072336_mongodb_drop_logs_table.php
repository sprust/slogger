<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

return new class extends Migration {
    // The Logs page reads the log files now, nothing writes this collection any more.
    protected string $connectionName = 'mongodb.logs';
    protected string $collectionName = 'logs';

    public $withinTransaction = false;

    public function up(): void
    {
        $database = $this->database();

        if (in_array($this->collectionName, $database->listCollections(), true)) {
            $database->command(['drop' => $this->collectionName]);
        }
    }

    /**
     * Brings back the collection and the indexes of 2024_12_22_172459_mongodb_create_logs_table,
     * not the documents.
     */
    public function down(): void
    {
        $database = $this->database();

        if (!in_array($this->collectionName, $database->listCollections(), true)) {
            $database->command(['create' => $this->collectionName]);
        }

        $secondsPerHour = 60 * 60;

        $database->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    'key'                => ['loggedAt' => 1],
                    'name'               => 'loggedAt_1',
                    'expireAfterSeconds' => $secondsPerHour * 36,
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

    private function database(): Database
    {
        $config = config("database.connections.$this->connectionName");

        return new Client(
            "mongodb://{$config['username']}:{$config['password']}@{$config['host']}:{$config['port']}",
            timeoutMs: $config['options']['socketTimeoutMS'] ?? null,
        )->selectDatabase($config['database']);
    }
};
