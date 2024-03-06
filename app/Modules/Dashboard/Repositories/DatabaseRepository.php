<?php

namespace App\Modules\Dashboard\Repositories;

use App\Modules\Dashboard\Dto\Objects\Database\DatabaseCollectionIndexObject;
use App\Modules\Dashboard\Dto\Objects\Database\DatabaseCollectionObject;
use App\Modules\Dashboard\Dto\Objects\Database\DatabaseObject;
use App\Modules\Dashboard\Dto\Objects\Database\DatabaseObjects;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;
use MongoDB\Model\BSONDocument;
use MongoDB\Model\DatabaseInfo;

readonly class DatabaseRepository implements DatabaseRepositoryInterface
{
    public function __construct(private Application $app)
    {
    }

    public function find(): DatabaseObjects
    {
        $databaseSizes = null;

        $databaseObjects = new DatabaseObjects();

        foreach (array_keys($this->app['config']['database.connections.mongodb']) as $connectionName) {
            /** @var Connection $connection */
            $connection = DB::connection("mongodb.$connectionName");

            $databaseSizes = is_null($databaseSizes)
                ? collect($connection->getMongoClient()->listDatabases())
                    ->keyBy(fn(DatabaseInfo $databaseInfo) => $databaseInfo->getName())
                    ->map(fn(DatabaseInfo $databaseInfo) => $databaseInfo->getSizeOnDisk())
                    ->toArray()
                : [];

            $databaseName = $connection->getDatabaseName();

            $databaseSize = $databaseSizes[$databaseName] ?? null;

            if ($databaseSize) {
                $databaseSize = $this->bitesToMb($databaseSize);
            }

            $collections = [];

            foreach ($connection->listCollections() as $collectionInfo) {
                $collectionName = $collectionInfo->getName();

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

                $collections[] = new DatabaseCollectionObject(
                    name: $collectionName,
                    size: $this->bitesToMb($storageStats['size']),
                    indexesSize: $this->bitesToMb($storageStats['totalIndexSize']),
                    totalSize: $this->bitesToMb($storageStats['totalSize']),
                    count: $storageStats['count'],
                    avgObjSize: $this->bitesToMb($storageStats['avgObjSize']),
                    indexes: collect($storageStats['indexSizes'])
                        ->map(
                            fn(int $indexSize, string $indexName) => new DatabaseCollectionIndexObject(
                                name: $indexName,
                                size: $this->bitesToMb($indexSize),
                                usage: $indexStats[$indexName]['accesses']['ops']
                            )
                        )
                        ->values()
                        ->toArray()
                );
            }

            $databaseObjects->add(
                new DatabaseObject(
                    name: $databaseName,
                    size: $databaseSize,
                    collections: $collections
                )
            );
        }

        return $databaseObjects;
    }

    private function bitesToMb(int $bites): float
    {
        return round($bites / 1024 / 1024, 3);
    }
}
