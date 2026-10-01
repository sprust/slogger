<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

return new class extends Migration {
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'traceTreeCache';

    public $withinTransaction = false;

    private const string INDEX_NAME = 'rootTraceId_1_parentTraceId_1_loggedAt_1__id_1';

    /**
     * Run the migrations.
     *
     * A tree too large to send whole is opened a branch at a time: the children of one node,
     * a page at a time, in the order the panel shows them. That is this index read front to
     * back — the node's children in loggedAt order, with _id to settle a tie and to carry the
     * cursor. Its first two fields also count the children of every node on the page, which
     * is how the panel knows which rows can be opened at all.
     */
    public function up(): void
    {
        $this->database()->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    'key'  => [
                        'rootTraceId'   => 1,
                        'parentTraceId' => 1,
                        'loggedAt'      => 1,
                        '_id'           => 1,
                    ],
                    'name' => self::INDEX_NAME,
                ],
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $collection = $this->database()->selectCollection($this->collectionName);

        if (!in_array(self::INDEX_NAME, array_column($collection->listIndexes(), 'name'), true)) {
            return;
        }

        $collection->dropIndex(self::INDEX_NAME);
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
