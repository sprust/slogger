<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

return new class extends Migration {
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'traceTreeCache';

    public $withinTransaction = false;

    private const int TTL_SECONDS = 60 * 60 * 24;

    /**
     * Run the migrations.
     *
     * An hour was short enough for a tree to start eating itself. The TTL counts from
     * each document's own createdAt, not from the end of the build, so a tree of four
     * million nodes — thirty-five minutes to an hour at the rates we see — has its first
     * levels expiring while the last ones are still being written. The root goes first,
     * because it is written first. What is left after that is a tree with no head, and a
     * count in the state that no longer matches anything in the collection.
     *
     * A day leaves twenty times the headroom of the longest build we have seen, and the
     * TTL keeps doing the only job it is for: collecting a cache nobody came back to.
     * Staleness is not its business either way — the state of a build expires an hour
     * after its last update, and a tree opened after that is wiped and rebuilt.
     *
     * collMod rather than a drop and a rebuild: the TTL is a property of the index, and
     * rebuilding one over four million documents to change a number is work for nothing.
     */
    public function up(): void
    {
        $this->setTtl(self::TTL_SECONDS);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->setTtl(60 * 60);
    }

    private function setTtl(int $seconds): void
    {
        $database = $this->database();

        $collection = $database->selectCollection($this->collectionName);

        if (!in_array('createdAt_1', array_column($collection->listIndexes(), 'name'), true)) {
            return;
        }

        $database->command([
            'collMod' => $this->collectionName,
            'index'   => [
                'name'               => 'createdAt_1',
                'expireAfterSeconds' => $seconds,
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
