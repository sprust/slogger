<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Repositories;

use App\Modules\Dashboard\Entities\DatabaseCollectionIndexStatObject;
use App\Modules\Dashboard\Entities\DatabaseCollectionStatObject;
use App\Modules\Dashboard\Entities\DatabaseStatObject;
use App\Services\Clickhouse\ClickhouseClient;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Services\Mongo\MongoConnectionFactory;
use Illuminate\Support\Arr;
use RuntimeException;
use SConcur\Bson\Int64;
use SConcur\Exceptions\TaskErrorException;
use SConcur\Exceptions\TaskExecutionException;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

readonly class DatabaseStatRepository
{
    /**
     * The listing is read in one batch rather than through a cursor. A collection entry
     * is a couple of hundred bytes against the 16 MB a reply may hold, so the batch runs
     * out long before the limit does — and if a database ever did outgrow it, the cursor
     * left open says so instead of the panel quietly losing collections.
     */
    private const int LIST_COLLECTIONS_BATCH_SIZE = 10000;

    public function __construct(
        private MongoConnectionFactory $connections,
        private ClickhouseClient $clickhouse,
    ) {
    }

    /**
     * The MongoDB databases, then the ClickHouse one that holds the traces. ClickHouse out of
     * reach leaves its entry out and is reported, so the MongoDB stats still refresh.
     *
     * @return DatabaseStatObject[]
     */
    public function find(): array
    {
        $databaseSizes = null;

        $databases = [];

        $memoryUsageSize = null;

        foreach ($this->connections->connectionNames() as $connectionName) {
            $client = $this->connections->client($connectionName);

            if (is_null($memoryUsageSize)) {
                $memoryUsageSize = $this->bitesToMb($this->heapSize($client));
            }

            if (is_null($databaseSizes)) {
                $databaseSizes = $this->databaseSizes($client);
            }

            $databaseName = $this->connections->databaseName($connectionName);

            $databaseSize = $databaseSizes[$databaseName] ?? null;

            $databaseSize = $databaseSize ? $this->bitesToMb($databaseSize) : 0;

            $database = $client->selectDatabase($databaseName);

            $collections = [];

            $totalDocumentsCount = 0;

            foreach ($this->collectionNames($database) as $collectionName) {
                $collection = $this->collectionStat($database, $collectionName);

                if (is_null($collection)) {
                    continue;
                }

                $totalDocumentsCount += $collection->count;

                $collections[] = $collection;
            }

            $databases[] = new DatabaseStatObject(
                name: $databaseName,
                size: $databaseSize,
                totalDocumentsCount: $totalDocumentsCount,
                memoryUsage: $memoryUsageSize,
                collections: $collections
            );
        }

        try {
            $databases[] = $this->clickhouseStat();
        } catch (ClickhouseQueryException $exception) {
            report($exception);
        }

        return $databases;
    }

    /**
     * The tables of the ClickHouse database in the shape of collections: the active parts
     * give the rows and the bytes, the skipping indexes their own size. Rows of a trace
     * written twice count twice until its parts are merged.
     *
     * @throws ClickhouseQueryException
     */
    private function clickhouseStat(): DatabaseStatObject
    {
        $tables = $this->clickhouse->select(
            sql: 'SELECT table, sum(rows) AS rows, sum(bytes_on_disk) AS total, '
            . 'sum(data_compressed_bytes) AS data, sum(secondary_indices_compressed_bytes) AS indexes '
            . 'FROM system.parts WHERE database = currentDatabase() AND active GROUP BY table ORDER BY table',
            queryIdPrefix: 'dashboard-database'
        );

        $indexes = $this->clickhouse->select(
            sql: 'SELECT table, name, data_compressed_bytes AS size FROM system.data_skipping_indices '
            . 'WHERE database = currentDatabase() ORDER BY table, name',
            queryIdPrefix: 'dashboard-database'
        );

        $memory = $this->clickhouse->select(
            sql: "SELECT value FROM system.metrics WHERE metric = 'MemoryTracking'",
            queryIdPrefix: 'dashboard-database'
        );

        $collections = [];

        foreach ($tables as $table) {
            $rows = (int) $table['rows'];

            $collections[] = new DatabaseCollectionStatObject(
                name: (string) $table['table'],
                size: $this->bitesToMb((int) $table['data']),
                indexesSize: $this->bitesToMb((int) $table['indexes']),
                totalSize: $this->bitesToMb((int) $table['total']),
                count: $rows,
                avgObjSize: $rows === 0 ? 0 : $this->bitesToMb((int) $table['total'] / $rows),
                indexes: array_values(
                    array_map(
                        fn(array $index) => new DatabaseCollectionIndexStatObject(
                            name: (string) $index['name'],
                            size: $this->bitesToMb((int) $index['size']),
                            // ClickHouse keeps no per-index access counter
                            usage: 0
                        ),
                        array_filter(
                            $indexes,
                            static fn(array $index): bool => $index['table'] === $table['table']
                        )
                    )
                ),
            );
        }

        return new DatabaseStatObject(
            name: 'clickhouse',
            size: $this->bitesToMb(array_sum(array_map(static fn(array $table) => (int) $table['total'], $tables))),
            totalDocumentsCount: array_sum(array_map(static fn(array $table) => (int) $table['rows'], $tables)),
            memoryUsage: $this->bitesToMb((int) ($memory[0]['value'] ?? 0)),
            collections: $collections
        );
    }

    /** The server's own memory, which is per process rather than per database. */
    private function heapSize(Client $client): float
    {
        $status = $client->selectDatabase('admin')->command(['serverStatus' => 1]);

        return $this->number($status['tcmalloc']['generic']['heap_size']);
    }

    /**
     * @return array<string, int|float>
     */
    private function databaseSizes(Client $client): array
    {
        $result = $client->selectDatabase('admin')->command(['listDatabases' => 1]);

        return Arr::mapWithKeys(
            $result['databases'],
            fn(array $database): array => [$database['name'] => $this->number($database['sizeOnDisk'])]
        );
    }

    /**
     * The names of the real collections, sorted, with the views left out: a view answers
     * neither $collStats nor $indexStats.
     *
     * @return string[]
     */
    private function collectionNames(Database $database): array
    {
        $result = $database->command([
            'listCollections' => 1,
            'cursor'          => ['batchSize' => self::LIST_COLLECTIONS_BATCH_SIZE],
        ]);

        if ((string) $result['cursor']['id'] !== '0') {
            throw new RuntimeException(
                "The collections of $database->name did not fit one listCollections batch."
            );
        }

        $names = [];

        foreach ($result['cursor']['firstBatch'] as $collection) {
            if ($collection['type'] === 'view' || $collection['name'] === 'system.views') {
                continue;
            }

            $names[] = $collection['name'];
        }

        sort($names);

        return $names;
    }

    /**
     * Null when the collection is gone since it was listed: the hourly trace cleaning drops them.
     */
    private function collectionStat(Database $database, string $collectionName): ?DatabaseCollectionStatObject
    {
        $collection = $database->selectCollection($collectionName);

        try {
            $storageStats = iterator_to_array(
                $collection->aggregate([
                    [
                        '$collStats' => [
                            'storageStats' => (object) [],
                        ],
                    ],
                ])
            )[0]['storageStats'];

            $indexUsageByName = Arr::mapWithKeys(
                iterator_to_array($collection->aggregate([['$indexStats' => (object) []]])),
                fn(array $indexStat): array => [$indexStat['name'] => (int) $this->number($indexStat['accesses']['ops'])]
            );
        } catch (TaskErrorException|TaskExecutionException $exception) {
            if ($this->collectionExists($database, $collectionName)) {
                throw $exception;
            }

            return null;
        }

        return new DatabaseCollectionStatObject(
            name: $collectionName,
            size: $this->bitesToMb($storageStats['size']),
            indexesSize: $this->bitesToMb($storageStats['totalIndexSize']),
            totalSize: $this->bitesToMb($storageStats['totalSize']),
            count: (int) $this->number($storageStats['count']),
            avgObjSize: $this->bitesToMb($storageStats['avgObjSize'] ?? 0),
            indexes: array_values(
                Arr::map(
                    (array) $storageStats['indexSizes'],
                    fn(Int64|int|float $indexSize, string $indexName) => new DatabaseCollectionIndexStatObject(
                        name: $indexName,
                        size: $this->bitesToMb($indexSize),
                        // $indexStats does not always cover what indexSizes lists — an
                        // index still being built has a size but no access counter yet —
                        // and the panel asking for the missing one is what used to throw.
                        usage: $indexUsageByName[$indexName] ?? 0
                    )
                )
            ),
        );
    }

    private function collectionExists(Database $database, string $collectionName): bool
    {
        $result = $database->command([
            'listCollections' => 1,
            'filter'          => ['name' => $collectionName],
            'nameOnly'        => true,
        ]);

        return count($result['cursor']['firstBatch']) > 0;
    }

    private function bitesToMb(Int64|int|float $bites): float
    {
        return round($this->number($bites) / 1024 / 1024, 3);
    }

    /**
     * A number as the driver handed it over.
     *
     * The Go side returns a Mongo integer that does not fit 32 bits as a BSON Int64
     * rather than a PHP int, and which of the two a field is depends on the value the
     * server happened to put there — a database's sizeOnDisk crosses the line while a
     * document count usually does not. So every number read out of a command or an
     * aggregation passes through here instead of being trusted to be an int.
     */
    private function number(Int64|int|float $value): int|float
    {
        return $value instanceof Int64 ? $value->toInt() : $value;
    }
}
