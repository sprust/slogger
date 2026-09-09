<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

/**
 * Everything a watcher produces, and how long it is kept.
 *
 * Not beside the watcher in MySQL. Its settings are a record — they have to be there for
 * as long as the watcher is, and they are what the receiver reads — but the incidents, the
 * events under them and the lines behind those all accumulate while the system runs. That
 * is the same shape as traces and the buffer, and it belongs where those live: in Mongo,
 * retired by a TTL index instead of by a cleanup nobody wrote.
 *
 * Which is also why deleting a watcher takes none of it away. There is no foreign key to
 * cascade and no sweep to run, and the history of a problem should not disappear because
 * somebody tidied up the watcher that found it.
 *
 * `watcherTimelines` is written by the Go receiver and needs no creating here — its first
 * bucket makes it. What it needs is the end this gives it.
 */
return new class extends Migration {
    // Not Migration::$connection: the database manager has no Mongo driver registered.
    // This names a `database.connections.mongodb.*` entry, read below as plain config.
    protected string $connectionName = 'mongodb.traces';

    // Nothing here is transactional, and the transaction the migrator would otherwise open
    // is on the default MySQL connection, which none of this touches.
    public $withinTransaction = false;

    /**
     * A month. Long enough that an incident is still there in the review after the
     * incident, short enough that nobody has to sweep it.
     */
    private const int INCIDENT_TTL_SECONDS = 60 * 60 * 24 * 30;

    /**
     * The TTL a closed incident rides on used to be `lastEventAt`, which also retired the
     * open ones. Dropped by name, because nothing else would: putIndexes reconciles the
     * indexes it is given and leaves the rest alone.
     */
    private const string RETIRED_INCIDENT_TTL_INDEX = 'lastEventAt_1';

    /**
     * Three days for a line. A watcher whose traffic never stops has `uat` rewritten every
     * fifteen seconds and never expires; one that has matched nothing for three days loses
     * a document that says nothing, and gets a fresh one from the next trace.
     */
    private const int TIMELINE_TTL_SECONDS = 60 * 60 * 24 * 3;

    public function up(): void
    {
        $database = $this->database();

        $this->dropIndex($database, 'watcherIncidents', self::RETIRED_INCIDENT_TTL_INDEX);

        $this->putIndexes($database, 'watcherIncidents', [
            // A month after it was closed, and only then. The TTL used to hang off
            // `lastEventAt` for every incident, which quietly removed the open ones too:
            // a problem nobody got to in a month lost the only record that it happened,
            // and the badge counting them went down on its own. Nothing closes an
            // incident but a person, and that has to include this.
            [
                'key'                    => ['closedAt' => 1],
                'name'                   => 'closedAt_1',
                'expireAfterSeconds'     => self::INCIDENT_TTL_SECONDS,
                'partialFilterExpression' => ['status' => 'closed'],
            ],
            // The open incident of one watcher, which every trigger looks for.
            ['key' => ['watcherId' => 1, 'status' => 1], 'name' => 'watcherId_1_status_1'],
            // The list: still open first, then newest. `opened` sorts after `closed`, so
            // the descending status is what puts the work queue at the top.
            ['key' => ['status' => -1, '_id' => -1], 'name' => 'status_-1__id_-1'],
        ]);

        $this->putIndexes($database, 'watcherIncidentEvents', [
            // A month from when it happened, on its own clock. Not tied to the incident
            // above it — Mongo retires documents, not the children of one — so an
            // incident left open for longer than this keeps its row and its count while
            // its earliest events go. That is the trade for not writing a sweep: the
            // count says how many times the watcher spoke, the events say what it saw
            // recently.
            [
                'key'                => ['occurredAt' => 1],
                'name'               => 'occurredAt_1',
                'expireAfterSeconds' => self::INCIDENT_TTL_SECONDS,
            ],
            ['key' => ['incidentId' => 1, '_id' => -1], 'name' => 'incidentId_1__id_-1'],
        ]);

        $this->putIndexes($database, 'watcherTimelines', [
            [
                'key'                => ['uat' => 1],
                'name'               => 'uat_1',
                'expireAfterSeconds' => self::TIMELINE_TTL_SECONDS,
            ],
        ]);
    }

    public function down(): void
    {
        $database = $this->database();

        foreach (['watcherIncidents', 'watcherIncidentEvents'] as $collectionName) {
            if (in_array($collectionName, $database->listCollections(), true)) {
                $database->command(['drop' => $collectionName]);
            }
        }

        // Dropped, not the collection: the receiver owns that one and may be writing to it
        // right now.
        $this->dropIndex($database, 'watcherTimelines', 'uat_1');
    }

    /**
     * Puts the given indexes on a collection, creating it if the first write has not.
     *
     * The same name with different options is an IndexOptionsConflict rather than a no-op,
     * so an index already there under another TTL, or under another partial filter, is
     * dropped and made again.
     *
     * @param array<int, array<string, mixed>> $indexes
     */
    private function putIndexes(Database $database, string $collectionName, array $indexes): void
    {
        if (!in_array($collectionName, $database->listCollections(), true)) {
            $database->command(['create' => $collectionName]);
        }

        $collection = $database->selectCollection($collectionName);

        $existing = [];

        foreach ($collection->listIndexes() as $index) {
            $existing[$index['name']] = $index;
        }

        $wanted = [];

        foreach ($indexes as $index) {
            $current = $existing[$index['name']] ?? null;

            if (!is_null($current)) {
                $same = ($current['expireAfterSeconds'] ?? null) === ($index['expireAfterSeconds'] ?? null)
                    && ($current['partialFilterExpression'] ?? null) == ($index['partialFilterExpression'] ?? null);

                if ($same) {
                    continue;
                }

                $collection->dropIndex($index['name']);
            }

            $wanted[] = $index;
        }

        if (!count($wanted)) {
            return;
        }

        $database->command([
            'createIndexes' => $collectionName,
            'indexes'       => $wanted,
        ]);
    }

    private function dropIndex(Database $database, string $collectionName, string $indexName): void
    {
        if (!in_array($collectionName, $database->listCollections(), true)) {
            return;
        }

        $collection = $database->selectCollection($collectionName);

        if (in_array($indexName, array_column($collection->listIndexes(), 'name'), true)) {
            $collection->dropIndex($indexName);
        }
    }

    /**
     * The connection, built here rather than taken from an application service: a migration
     * has to keep meaning what it meant on the day it ran, and application code moves on.
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
