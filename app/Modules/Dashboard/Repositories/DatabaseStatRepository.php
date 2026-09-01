<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Repositories;

use App\Modules\Dashboard\Entities\DatabaseCollectionIndexStatObject;
use App\Modules\Dashboard\Entities\DatabaseCollectionStatObject;
use App\Modules\Dashboard\Entities\DatabaseStatObject;
use App\Services\Mongo\MongoConnectionFactory;
use Illuminate\Support\Arr;
use RuntimeException;
use SConcur\Bson\Int64;
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

    public function __construct(private MongoConnectionFactory $connections)
    {
    }

    /**
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

        return $databases;
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

    private function collectionStat(Database $database, string $collectionName): DatabaseCollectionStatObject
    {
        $collection = $database->selectCollection($collectionName);

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
