<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Repositories;

use App\Modules\Dashboard\Entities\DatabaseCollectionIndexStatObject;
use App\Modules\Dashboard\Entities\DatabaseCollectionStatObject;
use App\Modules\Dashboard\Entities\DatabaseStatObject;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Throwable;

class DatabaseStatCacheRepository
{
    private string $cacheKey = 'dashboard:database-stat';

    public function __construct(
        private readonly CacheRepository $cache,
    ) {
    }

    /**
     * @param DatabaseStatObject[] $stats
     */
    public function put(array $stats): void
    {
        $this->cache->put(
            key: $this->cacheKey,
            value: json_encode(
                array_map(
                    fn(DatabaseStatObject $stat) => [
                        'name'                => $stat->name,
                        'size'                => $stat->size,
                        'totalDocumentsCount' => $stat->totalDocumentsCount,
                        'memoryUsage'         => $stat->memoryUsage,
                        'collections'         => array_map(
                            fn(DatabaseCollectionStatObject $col) => [
                                'name'        => $col->name,
                                'size'        => $col->size,
                                'indexesSize' => $col->indexesSize,
                                'totalSize'   => $col->totalSize,
                                'count'       => $col->count,
                                'avgObjSize'  => $col->avgObjSize,
                                'indexes'     => array_map(
                                    fn(DatabaseCollectionIndexStatObject $idx) => [
                                        'name'  => $idx->name,
                                        'size'  => $idx->size,
                                        'usage' => $idx->usage,
                                    ],
                                    $col->indexes
                                ),
                            ],
                            $stat->collections
                        ),
                    ],
                    $stats
                )
            ),
            ttl: 90,
        );
    }

    /**
     * @return DatabaseStatObject[]|null
     */
    public function find(): ?array
    {
        try {
            $raw = $this->cache->get($this->cacheKey);

            if ($raw === null) {
                return null;
            }

            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

            return array_map(
                fn(array $item) => new DatabaseStatObject(
                    name: $item['name'],
                    size: $item['size'],
                    totalDocumentsCount: $item['totalDocumentsCount'],
                    memoryUsage: $item['memoryUsage'],
                    collections: array_map(
                        fn(array $col) => new DatabaseCollectionStatObject(
                            name: $col['name'],
                            size: $col['size'],
                            indexesSize: $col['indexesSize'],
                            totalSize: $col['totalSize'],
                            count: $col['count'],
                            avgObjSize: $col['avgObjSize'],
                            indexes: array_map(
                                fn(array $idx) => new DatabaseCollectionIndexStatObject(
                                    name: $idx['name'],
                                    size: $idx['size'],
                                    usage: $idx['usage'],
                                ),
                                $col['indexes']
                            ),
                        ),
                        $item['collections']
                    ),
                ),
                $data
            );
        } catch (Throwable) {
            $this->cache->forget($this->cacheKey);

            return null;
        }
    }
}
