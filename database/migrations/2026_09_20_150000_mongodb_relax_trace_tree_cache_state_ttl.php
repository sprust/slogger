<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

return new class extends Migration {
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'traceTreeCacheStates';

    public $withinTransaction = false;

    private const int TTL_SECONDS = 60 * 60 * 20;

    /**
     * Run the migrations.
     *
     * The state decides how long a built tree lives, not the cache: a tree opened with no
     * state on record is wiped and built again, however many of its nodes are still there.
     * So an hour here meant an hour for the whole thing, and the day the cache was given
     * bought nothing.
     *
     * Twenty hours rather than the cache's twenty-four, and the gap is the point: the
     * state goes first, so there is never a state pointing at a tree the cache has already
     * started to expire. A build is answered by the tree it was built for, or by nothing.
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

        if (!in_array('updatedAt_1', array_column($collection->listIndexes(), 'name'), true)) {
            return;
        }

        $database->command([
            'collMod' => $this->collectionName,
            'index'   => [
                'name'               => 'updatedAt_1',
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
