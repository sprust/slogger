<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

return new class extends Migration {
    // Traces are read from ClickHouse now, which needs no index per set of filters: the
    // registry of those indexes has nothing left to describe. The indexes themselves stay
    // on the old hourly collections of tracesPeriodic, which nothing reads either.
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'traceDynamicIndexes';

    public $withinTransaction = false;

    public function up(): void
    {
        $database = $this->database();

        if (in_array($this->collectionName, $database->listCollections(), true)) {
            $database->command(['drop' => $this->collectionName]);
        }
    }

    /**
     * Brings back the collection and the index of 2024_07_19_182736_mongodb_create_trace_dynamic_indexes_collection,
     * not the documents. The validator of that migration is not restored: nothing writes here.
     */
    public function down(): void
    {
        $database = $this->database();

        if (!in_array($this->collectionName, $database->listCollections(), true)) {
            $database->command(['create' => $this->collectionName]);
        }

        $database->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    'key'    => ['name' => 1],
                    'name'   => 'name_1',
                    'unique' => true,
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
