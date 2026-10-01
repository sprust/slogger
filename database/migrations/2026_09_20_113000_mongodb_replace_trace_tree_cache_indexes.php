<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

return new class extends Migration {
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'traceTreeCache';

    public $withinTransaction = false;

    /**
     * Run the migrations.
     *
     * `_id` joins (rootTraceId, depth) because that is how a level is read: the builder
     * pages through it by a cursor on `_id`, and without the third field the first page
     * of a level costs the whole level — the index gives the set, and the sort by `_id`
     * is then done in memory. Measured on two million documents, a level of fifty
     * thousand: 210-250 ms and a blocking SORT, against 6-8 ms and a thousand keys with
     * the third field. The wider the level, the worse it gets, and past a hundred
     * megabytes of sort the query would stop working altogether.
     *
     * The index it replaces is its own prefix, so nothing that used one loses anything.
     *
     * The two parentTraceId indexes go because nothing reads them: the field is written
     * and read back as a value, never filtered on, and `order` does not exist in a
     * document of this collection at all — they belong to a shape this cache had before
     * it was rewritten. They were also not cheap: two of six indexes, and two thirds of
     * all index bytes (238 MB of 357 MB on two million documents), written on every node
     * of every tree.
     */
    public function up(): void
    {
        $database = $this->database();

        $database->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    'key'  => [
                        'rootTraceId' => 1,
                        'depth'       => 1,
                        '_id'         => 1,
                    ],
                    'name' => 'rootTraceId_1_depth_1__id_1',
                ],
            ],
        ]);

        foreach (['rootTraceId_1_depth_1', 'parentTraceId_1_order_1', 'parentTraceId_1_traceId_1'] as $indexName) {
            $this->dropIfExists($database, $indexName);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $database = $this->database();

        $database->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    'key'  => [
                        'rootTraceId' => 1,
                        'depth'       => 1,
                    ],
                    'name' => 'rootTraceId_1_depth_1',
                ],
                [
                    'key'  => [
                        'parentTraceId' => 1,
                        'order'         => 1,
                    ],
                    'name' => 'parentTraceId_1_order_1',
                ],
                [
                    'key'  => [
                        'parentTraceId' => 1,
                        'traceId'       => 1,
                    ],
                    'name' => 'parentTraceId_1_traceId_1',
                ],
            ],
        ]);

        $this->dropIfExists($database, 'rootTraceId_1_depth_1__id_1');
    }

    /**
     * An index absent here is not a failed migration: which of these a given environment
     * carries depends on how old it is, and two of them are in no migration at all.
     */
    private function dropIfExists(Database $database, string $indexName): void
    {
        $collection = $database->selectCollection($this->collectionName);

        if (!in_array($indexName, array_column($collection->listIndexes(), 'name'), true)) {
            return;
        }

        $collection->dropIndex($indexName);
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
