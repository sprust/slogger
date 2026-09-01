<?php

declare(strict_types=1);

namespace App\Services\Mongo;

use SConcur\Features\Mongodb\Payloads\Support\IndexName;

/**
 * The collection and index operations the migrations need, as raw Mongo commands.
 *
 * The SConcur collection API covers what the application does at runtime, where an index
 * is only ever keys and a name. A migration asks for more than that — a TTL, a uniqueness
 * constraint, a $jsonSchema validator — so it goes through the database commands, which
 * take the full specification.
 *
 * Failures surface: a command the server rejects throws out of the driver, and the
 * migration fails with it. Two cases the server lets through, as it did for the ORM this
 * replaces: dropping a collection that does not exist, and creating one that already
 * exists with the very same options. A later migration that only adds indexes but still
 * opens with createCollection() rests on the second — and gets NamespaceExists rather
 * than silence if it ever passes options that differ from the ones in place.
 */
readonly class MongoSchema
{
    public function __construct(private MongoConnectionFactory $connections)
    {
    }

    /**
     * @param array<string, mixed> $options `validator`, `capped`, and the rest of `create`
     */
    public function createCollection(string $connectionName, string $collectionName, array $options = []): void
    {
        $this->connections->database($connectionName)->command([
            'create' => $collectionName,
            ...$options,
        ]);
    }

    public function dropCollection(string $connectionName, string $collectionName): void
    {
        $this->connections->database($connectionName)->command(['drop' => $collectionName]);
    }

    /**
     * The index is named the way MongoDB would have named it, so an index created here is
     * the one an environment built by an earlier migration already has.
     *
     * @param array<string, int|string> $keys
     * @param array<string, mixed>      $options `expireAfterSeconds`, `unique`, and the rest
     */
    public function createIndex(
        string $connectionName,
        string $collectionName,
        array $keys,
        array $options = [],
    ): void {
        $this->connections->database($connectionName)->command([
            'createIndexes' => $collectionName,
            'indexes'       => [
                [
                    'key'  => $keys,
                    'name' => IndexName::fromKeys($keys),
                    ...$options,
                ],
            ],
        ]);
    }

    /** Every index of the collection but `_id_`, which Mongo does not let go. */
    public function dropIndexes(string $connectionName, string $collectionName): void
    {
        $this->connections->database($connectionName)->command([
            'dropIndexes' => $collectionName,
            'index'       => '*',
        ]);
    }

    public function dropIndex(string $connectionName, string $collectionName, string $indexName): void
    {
        $this->connections->database($connectionName)->command([
            'dropIndexes' => $collectionName,
            'index'       => $indexName,
        ]);
    }
}
