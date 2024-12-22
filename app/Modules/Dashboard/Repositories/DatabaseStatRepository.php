<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Repositories;

use App\Modules\Dashboard\Contracts\Repositories\DatabaseStatRepositoryInterface;
use App\Modules\Dashboard\Entities\DatabaseCollectionIndexStatObject;
use App\Modules\Dashboard\Entities\DatabaseCollectionStatObject;
use App\Modules\Dashboard\Entities\DatabaseStatObject;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
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

        $memoryUsageSize = null;

        foreach (array_keys($this->app['config']['database.connections.mongodb']) as $connectionName) {
            /** @var Connection $connection */
            $connection = DB::connection("mongodb.$connectionName");

            if (is_null($memoryUsageSize)) {
                $memoryUsageSize = $this->bitesToMb(
                    $connection->getManager()
                        ->executeCommand('admin', new Command(['serverStatus' => 1]))
                        ->toArray()[0]
                        ->tcmalloc
                        ->generic
                        ->heap_size
                );
            }

            if (is_null($databaseSizes)) {
                $databaseSizes = collect($connection->getMongoClient()->listDatabases())
                    ->keyBy(fn(DatabaseInfo $databaseInfo) => $databaseInfo->getName())
                    ->map(fn(DatabaseInfo $databaseInfo) => $databaseInfo->getSizeOnDisk())
                    ->toArray();
            }

            $databaseName = $connection->getDatabaseName();

            $databaseSize = $databaseSizes[$databaseName] ?? null;

            $databaseSize = $databaseSize ? $this->bitesToMb($databaseSize) : 0;

            $collections = [];

            $totalDocumentsCount = 0;

            $listCollections = Arr::sort(
                iterator_to_array($connection->listCollections()),
                fn($collectionInfo) => $collectionInfo->getName()
            );

            foreach ($listCollections as $collectionInfo) {
                if ($collectionInfo->getType() === 'view') {
                    continue;
                }

                $collectionName = $collectionInfo->getName();

                if ($collectionName === 'system.views') {
                    continue;
                }

                $collection = $connection->selectCollection($collectionName);

                $collStats = iterator_to_array(
                    $collection->aggregate([
                        [
                            '$collStats' => [
                                'storageStats' => (object) [],
                            ],
                        ],
                    ])
                )[0];

                $indexStatsKeyByName = Arr::keyBy(
                    iterator_to_array($collection->aggregate([
                        [
                            '$indexStats' => (object) [],
                        ],
                    ])),
                    fn(BSONDocument $indexStat) => $indexStat->name
                );

                $storageStats = $collStats['storageStats'];

                $documentsCount = $storageStats['count'];

                $totalDocumentsCount += $documentsCount;

                $collections[] = new DatabaseCollectionStatObject(
                    name: $collectionName,
                    size: $this->bitesToMb($storageStats['size']),
                    indexesSize: $this->bitesToMb($storageStats['totalIndexSize']),
                    totalSize: $this->bitesToMb($storageStats['totalSize']),
                    count: $documentsCount,
                    avgObjSize: $this->bitesToMb($storageStats['avgObjSize'] ?? 0),
                    indexes: array_values(
                        Arr::map(
                            (array) $storageStats['indexSizes'],
                            fn(int $indexSize, string $indexName) => new DatabaseCollectionIndexStatObject(
                                name: $indexName,
                                size: $this->bitesToMb($indexSize),
                                usage: $indexStatsKeyByName[$indexName]['accesses']['ops']
                            )
                        )
                    ),
                );
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

    private function bitesToMb(int $bites): float
    {
        return round($bites / 1024 / 1024, 3);
    }
}
