<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

/**
 * Brings the buffer's indexes to the set its queries actually use.
 *
 * Stated as a target rather than as a list of drops, because the collection has drifted
 * from what the two earlier migrations describe: the installation this was written on
 * carries `sid_1_cat_1`, which neither of them creates, and none of the four indexes the
 * first one does. Naming what should be there is the only form of this that ends with
 * every installation in the same state, whatever it drifted into.
 *
 * The buffer is queried two ways. buffer_repository.FindMany scans it —
 * `find({}).sort({cat: 1}).limit(...)` — and MarkFailed reads back by `_id` and `att`;
 * everything else addresses it by `_id`. Nothing outside the receiver touches the
 * collection. So `_id_` and an index on `cat` is the whole of what is needed, and the TTL
 * rides on the latter.
 */
return new class extends Migration {
    // Not Migration::$connection: the database manager has no Mongo driver registered.
    // This names a `database.connections.mongodb.*` entry, read below as plain config.
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'buffer';

    // Nothing here is transactional, and the transaction the migrator would otherwise
    // open is on the default MySQL connection, which none of this touches.
    public $withinTransaction = false;

    private const string ID_INDEX = '_id_';
    private const string SORT_INDEX = 'cat_1';
    private const int SORT_INDEX_TTL_SECONDS = 60 * 60 * 6;

    public function up(): void
    {
        $database = $this->database();
        $collection = $database->selectCollection($this->collectionName);

        $existing = $collection->listIndexes();

        $hasSortIndex = false;

        foreach ($existing as $index) {
            $indexName = $index['name'];

            if ($indexName === self::ID_INDEX) {
                continue;
            }

            // Kept only if its options are the ones wanted too. A `cat_1` without the TTL
            // is exactly the drift this migration is for, and leaving it would end with
            // this installation in a different state from the rest and nothing said about
            // it. createIndexes cannot amend it — same name, different options is
            // IndexOptionsConflict — so it is dropped here and made again below.
            if (
                $indexName === self::SORT_INDEX
                && ($index['expireAfterSeconds'] ?? null) === self::SORT_INDEX_TTL_SECONDS
            ) {
                $hasSortIndex = true;

                continue;
            }

            // Written to the application log, because this drops whatever it finds —
            // including an index somebody added by hand — and down() does not put those
            // back. The name in the log is the only record that it was ever there.
            Log::warning("Dropping index $indexName on $this->collectionName");

            $collection->dropIndex($indexName);
        }

        if ($hasSortIndex) {
            return;
        }

        $database->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    'key'  => ['cat' => 1],
                    'name' => self::SORT_INDEX,
                    // The buffer is a queue, not a store: a document still here six hours
                    // after it arrived is one no pass managed to save or to reject, and
                    // keeping it costs more than it is worth.
                    'expireAfterSeconds' => self::SORT_INDEX_TTL_SECONDS,
                ],
            ],
        ]);
    }

    /**
     * Puts back what the two earlier migrations created, so that rolling back to either
     * of them lands where they left the collection.
     *
     * Indexes this migration dropped that no migration ever created are not restored:
     * there is no version of this collection they belong to.
     *
     * Restoring `lat_1` is inert, which is worth saying because it does not look it: it is
     * a TTL on the client's own logged-at time, already in the past for every buffered
     * document. It expires nothing all the same, because `lat` is stored as the string it
     * arrived as (dto.go: `LoggedAt interface{}`, written through unchanged) and MongoDB
     * expires BSON dates only. Were it ever stored as a date, this rollback would empty
     * the buffer on the first sweep.
     */
    public function down(): void
    {
        $this->database()->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    'key'  => ['tid' => 1],
                    'name' => 'tid_1',
                ],
                [
                    'key'  => ['sid' => 1, 'tid' => 1],
                    'name' => 'sid_1_tid_1',
                ],
                [
                    'key'  => ['lat' => 1, '__ins' => 1, '__upd' => 1],
                    'name' => 'lat_1___ins_1___upd_1',
                ],
                [
                    'key'                => ['lat' => 1],
                    'name'               => 'lat_1',
                    'expireAfterSeconds' => 60 * 60,
                ],
                [
                    'key'  => ['op' => 1, 'cat' => 1],
                    'name' => 'op_1_cat_1',
                ],
            ],
        ]);
    }

    /**
     * The connection, built here rather than taken from an application service.
     *
     * A migration has to keep meaning what it meant on the day it ran, and application
     * code moves on. What it may lean on is what does not: the configuration keys, the
     * driver, and the log facade. Index names are spelled out for the same reason — they
     * are what the collection actually carries, not what a helper would derive today.
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
