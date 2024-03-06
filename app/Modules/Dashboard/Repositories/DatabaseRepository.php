<?php

namespace App\Modules\Dashboard\Repositories;

use App\Modules\Dashboard\Dto\Objects\Database\CollectionIndexObject;
use App\Modules\Dashboard\Dto\Objects\Database\CollectionObject;
use App\Modules\Dashboard\Dto\Objects\Database\DatabaseObject;
use App\Modules\Dashboard\Dto\Objects\Database\DatabaseObjects;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;
use MongoDB\Model\DatabaseInfo;

readonly class DatabaseRepository implements DatabaseRepositoryInterface
{
    public function __construct(private Application $app)
    {
    }

    public function index(): DatabaseObjects
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

            $databaseSizeInMb = $databaseSizes[$databaseName] ?? null;

            if ($databaseSizeInMb) {
                $databaseSizeInMb = $this->bitesToMb($databaseSizeInMb);
            }

            $collections = [];

            foreach ($connection->listCollections() as $collectionInfo) {
                $collectionName = $collectionInfo->getName();

                $collection = $connection->selectCollection($collectionName);

                $stats = collect(
                    $collection->aggregate([
                        [
                            '$collStats' => [
                                'storageStats' => (object) [],
                            ],
                        ],
                    ])
                )[0];

                $storageStats = $stats['storageStats'];

                $collections[] = new CollectionObject(
                    name: $collectionName,
                    sizeInMb: $this->bitesToMb($storageStats['size']),
                    indexesSizeInMb: $this->bitesToMb($storageStats['totalIndexSize']),
                    totalSizeInMb: $this->bitesToMb($storageStats['totalSize']),
                    count: $storageStats['count'],
                    avgObjSizeInMb: $this->bitesToMb($storageStats['avgObjSize']),
                    indexes: collect($storageStats['indexSizes'])
                        ->map(
                            fn(int $indexSize, string $indexName) => new CollectionIndexObject(
                                name: $indexName,
                                sizeInMb: $this->bitesToMb($storageStats['indexSizes'][$indexName])
                            )
                        )
                        ->values()
                        ->toArray()
                );
            }

            $databaseObjects->add(
                new DatabaseObject(
                    name: $databaseName,
                    sizeInMb: $databaseSizeInMb,
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
