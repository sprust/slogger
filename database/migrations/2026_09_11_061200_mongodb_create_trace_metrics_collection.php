<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

return new class extends Migration {
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'traceMetrics';

    public $withinTransaction = false;

    private const int TTL_SECONDS = 60 * 60 * 25;

    public function up(): void
    {
        $database = $this->database();

        if (!in_array($this->collectionName, $database->listCollections(), true)) {
            $database->command(['create' => $this->collectionName]);
        }

        $database->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    'key'    => ['sid' => 1, 'tp' => 1, 't' => 1],
                    'name'   => 'sid_1_tp_1_t_1',
                    'unique' => true,
                ],
                [
                    'key'                => ['t' => 1],
                    'name'               => 't_1',
                    'expireAfterSeconds' => self::TTL_SECONDS,
                ],
            ],
        ]);
    }

    public function down(): void
    {
        $database = $this->database();

        if (in_array($this->collectionName, $database->listCollections(), true)) {
            $database->command(['drop' => $this->collectionName]);
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
