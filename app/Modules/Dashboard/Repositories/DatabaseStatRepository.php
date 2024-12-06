<?php

namespace App\Modules\Dashboard\Repositories;

use App\Modules\Dashboard\Contracts\Repositories\DatabaseStatRepositoryInterface;
use App\Modules\Dashboard\Entities\DatabaseCollectionIndexStatObject;
use App\Modules\Dashboard\Entities\DatabaseCollectionStatObject;
use App\Modules\Dashboard\Entities\DatabaseStatObject;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\Exception;
use MongoDB\Laravel\Connection;
use MongoDB\Model\BSONDocument;
use MongoDB\Model\DatabaseInfo;

readonly class DatabaseStatRepository implements DatabaseStatRepositoryInterface
{
    public function __construct(private Application $app)
    {
    }

    /**
     * @throws Exception
     */
    public function find(): array
    {
        $databaseSizes = null;

        $databases = [];

        foreach (array_keys($this->app['config']['database.connections.mongodb']) as $connectionName) {
            /** @var Connection $connection */
            $connection = DB::connection("mongodb.$connectionName");

            $memoryUsageSize = $this->bitesToMb(
                $connection->getManager()
                    ->executeCommand('admin', new Command(['serverStatus' => 1]))
                    ->toArray()[0]
                    ->tcmalloc
                    ->generic->heap_size
            );

            $databaseSizes = is_null($databaseSizes)
                ? collect($connection->getMongoClient()->listDatabases())
                    ->keyBy(fn(DatabaseInfo $databaseInfo) => $databaseInfo->getName())
                    ->map(fn(DatabaseInfo $databaseInfo) => $databaseInfo->getSizeOnDisk())
                    ->toArray()
                : [];

            $databaseName = $connection->getDatabaseName();

            $databaseSize = $databaseSizes[$databaseName] ?? null;

            $databaseSize = $databaseSize ? $this->bitesToMb($databaseSize) : 0;

            $collections = [];

            foreach ($connection->listCollections() as $collectionInfo) {
                if ($collectionInfo->getType() === 'view') {
                    continue;
                }

                $collectionName = $collectionInfo->getName();

                if ($collectionName === 'system.views') {
                    continue;
                }

                $collection = $connection->selectCollection($collectionName);

                $collStats = collect(
                    $collection->aggregate([
                        [
                            '$collStats' => [
                                'storageStats' => (object) [],
                            ],
                        ],
                    ])
                )[0];

                $indexStats =
                    collect($collection->aggregate([
                        [
                            '$indexStats' => (object) [],
                        ],
                    ]))
                        ->keyBy(fn(BSONDocument $indexStat) => $indexStat->name)
                        ->toArray();

                $storageStats = $collStats['storageStats'];

                $collections[] = new DatabaseCollectionStatObject(
                    name: $collectionName,
                    size: $this->bitesToMb($storageStats['size']),
                    indexesSize: $this->bitesToMb($storageStats['totalIndexSize']),
                    totalSize: $this->bitesToMb($storageStats['totalSize']),
                    count: $storageStats['count'],
                    avgObjSize: $this->bitesToMb($storageStats['avgObjSize'] ?? 0),
                    indexes: collect($storageStats['indexSizes'])
                        ->map(
                            fn(int $indexSize, string $indexName) => new DatabaseCollectionIndexStatObject(
                                name: $indexName,
                                size: $this->bitesToMb($indexSize),
                                usage: $indexStats[$indexName]['accesses']['ops']
                            )
                        )
                        ->values()
                        ->toArray()
                );
            }

            $databases[] = new DatabaseStatObject(
                name: $databaseName,
                size: $databaseSize,
                memoryUsage: $memoryUsageSize,
                collections: $collections
            );
        }

        return $databases;
    }

    private function bitesToMb(int $bites): float
    {
        return round($bites / 1024 / 1024, 3);
    }
}
