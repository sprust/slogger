<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

return new class extends Migration {
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'pendingTraces';

    public $withinTransaction = false;

    private const string TTL_INDEX_NAME = 'dat_1';

    private const int TTL_SECONDS = 60 * 60;

    public function up(): void
    {
        $this->database()->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    'key'                => ['dat' => 1],
                    'name'               => self::TTL_INDEX_NAME,
                    'expireAfterSeconds' => self::TTL_SECONDS,
                ],
            ],
        ]);
    }

    public function down(): void
    {
        $this->database()->command([
            'dropIndexes' => $this->collectionName,
            'index'       => self::TTL_INDEX_NAME,
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
