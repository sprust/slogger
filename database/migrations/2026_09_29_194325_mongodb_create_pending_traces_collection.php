<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

/**
 * The traces the receiver's transporter keeps while they wait for their other half: a
 * create for its update, an update for its create. One document per service and trace,
 * `_id` = "<sid>:<tid>", read and written by id only, so the TTL is the one index it needs.
 *
 * A trace whose other half never comes is dropped by the TTL silently: the half that came
 * is already in ClickHouse, or is an update with nothing to attach to. An update that
 * arrives later than this finds nothing here and reads its trace from ClickHouse instead.
 */
return new class extends Migration {
    // Not Migration::$connection: the database manager has no Mongo driver registered.
    // This names a `database.connections.mongodb.*` entry, read below as plain config.
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'pendingTraces';

    // Nothing here is transactional, and the transaction the migrator would otherwise
    // open is on the default MySQL connection, which none of this touches.
    public $withinTransaction = false;

    private const string TTL_INDEX_NAME = 'uat_1';

    private const int TTL_SECONDS = 60 * 60 * 3;

    public function up(): void
    {
        $database = $this->database();

        $database->command(['create' => $this->collectionName]);

        $database->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    // `uat`, the moment of the last write: a trace that keeps receiving
                    // halves (a create sent twice) keeps waiting.
                    'key'                => ['uat' => 1],
                    'name'               => self::TTL_INDEX_NAME,
                    'expireAfterSeconds' => self::TTL_SECONDS,
                ],
            ],
        ]);
    }

    public function down(): void
    {
        $this->database()->command(['drop' => $this->collectionName]);
    }

    /**
     * The connection, built here rather than taken from an application service: a
     * migration has to keep meaning what it meant on the day it ran.
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
