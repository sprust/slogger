<?php

namespace App\Modules\Dashboard\Repositories\Database;

use App\Modules\Dashboard\Repositories\Database\Dto\DatabaseCollectionDto;
use App\Modules\Dashboard\Repositories\Database\Dto\DatabaseCollectionIndexDto;
use App\Modules\Dashboard\Repositories\Database\Dto\DatabaseDto;
use App\Modules\Dashboard\Repositories\Database\Dto\DatabasesDto;
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

    public function find(): DatabasesDto
    {
        $databaseSizes = null;

        $databasesDto = new DatabasesDto();

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

                $collections[] = new DatabaseCollectionDto(
                    name: $collectionName,
                    size: $this->bitesToMb($storageStats['size']),
                    indexesSize: $this->bitesToMb($storageStats['totalIndexSize']),
                    totalSize: $this->bitesToMb($storageStats['totalSize']),
                    count: $storageStats['count'],
                    avgObjSize: $this->bitesToMb($storageStats['avgObjSize'] ?? 0),
                    indexes: collect($storageStats['indexSizes'])
                        ->map(
                            fn(int $indexSize, string $indexName) => new DatabaseCollectionIndexDto(
                                name: $indexName,
                                size: $this->bitesToMb($indexSize),
                                usage: $indexStats[$indexName]['accesses']['ops']
                            )
                        )
                        ->values()
                        ->toArray()
                );
            }

            $databasesDto->add(
                new DatabaseDto(
                    name: $databaseName,
                    size: $databaseSize,
                    collections: $collections
                )
            );
        }

        return $databasesDto;
    }

    private function bitesToMb(int $bites): float
    {
        return round($bites / 1024 / 1024, 3);
    }
}
