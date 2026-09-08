<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

return new class extends Migration {
    protected string $connectionName = 'mongodb.traces';

    public $withinTransaction = false;

    private const int NOTIFICATION_TTL_SECONDS = 60 * 60 * 24 * 30;

    private const string COLLECTION = 'notifications';

    public function up(): void
    {
        $database = $this->database();

        if (!in_array(self::COLLECTION, $database->listCollections(), true)) {
            $database->command(['create' => self::COLLECTION]);
        }

        $collection = $database->selectCollection(self::COLLECTION);

        $existing = array_column(iterator_to_array($collection->listIndexes()), 'name');

        $indexes = array_values(
            array_filter(
                [
                    [
                        'key'                => ['createdAt' => 1],
                        'name'               => 'createdAt_1',
                        'expireAfterSeconds' => self::NOTIFICATION_TTL_SECONDS,
                    ],
                    ['key' => ['channelId' => 1, '_id' => -1], 'name' => 'channelId_1__id_-1'],
                ],
                static fn(array $index): bool => !in_array($index['name'], $existing, true)
            )
        );

        if (!count($indexes)) {
            return;
        }

        $database->command([
            'createIndexes' => self::COLLECTION,
            'indexes'       => $indexes,
        ]);
    }

    public function down(): void
    {
        $database = $this->database();

        if (in_array(self::COLLECTION, $database->listCollections(), true)) {
            $database->command(['drop' => self::COLLECTION]);
        }
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
