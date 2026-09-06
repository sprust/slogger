<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

/**
 * Gives the invalid buffer the collection it is actually written to, and a TTL.
 *
 * The receiver writes to the collection named by its own MONGODB_COLL_INVALID_BUFFER,
 * which is and has always been `invalidBuffer` (servers/receiver/.env.example). The
 * migration that set this up created `bufferInvalid` instead — a name nothing else in the
 * repository uses. So the indexed collection stayed empty while the one receiving
 * documents was created implicitly by the first insert, with no index and no TTL, and has
 * grown without a bound ever since.
 *
 * Which means the first TTL sweep after this migration deletes that whole backlog at once,
 * on a collection that until now had no index at all. Expected, and the point of the
 * exercise, but not a quiet migration on an installation that has been running a while.
 */
return new class extends Migration {
    // Not Migration::$connection: the database manager has no Mongo driver registered.
    // This names a `database.connections.mongodb.*` entry, read below as plain config.
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'invalidBuffer';
    protected string $strayCollectionName = 'bufferInvalid';

    // Nothing here is transactional, and the transaction the migrator would otherwise
    // open is on the default MySQL connection, which none of this touches.
    public $withinTransaction = false;

    private const string TTL_INDEX_NAME = 'iat_1';

    private const int TTL_SECONDS = 60 * 60 * 24 * 3;

    public function up(): void
    {
        $database = $this->database();
        $collection = $database->selectCollection($this->collectionName);

        if (!in_array($this->collectionName, $database->listCollections(), true)) {
            $database->command(['create' => $this->collectionName]);
        }

        // Same name with different options is IndexOptionsConflict, not a no-op, so an
        // installation that already carries this index with another TTL has it replaced
        // rather than the migration failing on it.
        $hasIndex = false;

        foreach ($collection->listIndexes() as $index) {
            if ($index['name'] !== self::TTL_INDEX_NAME) {
                continue;
            }

            if (($index['expireAfterSeconds'] ?? null) === self::TTL_SECONDS) {
                $hasIndex = true;

                break;
            }

            $collection->dropIndex(self::TTL_INDEX_NAME);

            break;
        }

        // Only the index is skipped, not the rest of the method: returning from here would
        // mean a run interrupted between the two halves could never finish the second one,
        // however many times the migration is re-run.
        if (!$hasIndex) {
            $this->createTtlIndex($database);
        }

        // Dropped only while empty. It should be — nothing ever wrote to it — but an
        // installation that once ran with the other name would lose whatever is in it,
        // and a migration is the wrong place to find that out.
        if (!in_array($this->strayCollectionName, $database->listCollections(), true)) {
            return;
        }

        if ($database->selectCollection($this->strayCollectionName)->countDocuments([]) === 0) {
            $database->command(['drop' => $this->strayCollectionName]);
        }
    }

    private function createTtlIndex(Database $database): void
    {
        $database->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    // `iat`, not `cat`. The receiver stamps `iat` on everything it moves
                    // here, while `cat` is only on the documents that came from the buffer
                    // whole — the ones that failed to decode carry their id, their raw
                    // bytes and the error, and nothing else. So a TTL on `cat` would never
                    // expire those at all, and would measure the rest from when they
                    // entered the buffer rather than from when they were set aside.
                    'key'                => ['iat' => 1],
                    'name'               => self::TTL_INDEX_NAME,
                    'expireAfterSeconds' => self::TTL_SECONDS,
                ],
            ],
        ]);
    }

    public function down(): void
    {
        $database = $this->database();
        $collection = $database->selectCollection($this->collectionName);

        // Guarded rather than dropped outright: dropIndexes raises IndexNotFound when the
        // index is not there, and as the first statement here that would take the
        // bufferInvalid restore below down with it and leave the rollback half applied.
        if (in_array(self::TTL_INDEX_NAME, array_column($collection->listIndexes(), 'name'), true)) {
            $collection->dropIndex(self::TTL_INDEX_NAME);
        }

        // Put back as the migration that made it left it, so that rolling this one back
        // and the earlier one forward again do not collide.
        if (in_array($this->strayCollectionName, $database->listCollections(), true)) {
            return;
        }

        $database->command(['create' => $this->strayCollectionName]);

        $database->command([
            'createIndexes' => $this->strayCollectionName,
            'indexes'       => [
                [
                    'key'                => ['cat' => 1],
                    'name'               => 'cat_1',
                    'expireAfterSeconds' => 60 * 60 * 36,
                ],
            ],
        ]);
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
