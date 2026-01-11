<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Services;

use App\Models\Traces\TraceTree;
use App\Modules\Trace\Entities\Trace\TraceCollectionNameObjects;
use Closure;
use Illuminate\Support\Carbon;
use Iterator;
use MongoDB\Database;
use RuntimeException;
use SConcur\Features\Mongodb\Connection\Collection;
use SConcur\Features\Mongodb\Connection\Database as SconcurDatabase;
use Throwable;

class PeriodicTraceService
{
    /** @var array<string, Collection> */
    private array $connectionsCache = [];
    private int $connectionsCachedAtSec;
    private int $connectionsCachedLifeTimeSec = 60;

    public function __construct(
        private readonly Database $database,
        private readonly SconcurDatabase $sconcurDatabase,
        private readonly PeriodicTraceCollectionNameService $periodicTraceCollectionNameService
    ) {
        $this->connectionsCachedAtSec = time();
    }

    public function selectCollectionByName(string $collectionName): Collection
    {
        return $this->sconcurDatabase->selectCollection($collectionName);
    }

    /**
     * @return string[]
     */
    public function detectCollectionNamesReverse(?Carbon $loggedAtFrom = null, ?Carbon $loggedAtTo = null): array
    {
        return array_values(
            array_reverse(
                $this->detectCollectionNames(
                    loggedAtFrom: $loggedAtFrom,
                    loggedAtTo: $loggedAtTo
                )
            )
        );
    }

    /**
     * @return string[]
     */
    public function detectCollectionNames(?Carbon $loggedAtFrom = null, ?Carbon $loggedAtTo = null): array
    {
        return $this->periodicTraceCollectionNameService->filterCollectionNamesByPeriod(
            collectionNames: iterator_to_array($this->database->listCollectionNames()),
            from: $loggedAtFrom,
            to: $loggedAtTo
        );
    }

    public function initCollectionByName(string $collectionName): Collection
    {
        if ((time() - $this->connectionsCachedAtSec) > $this->connectionsCachedLifeTimeSec) {
            $this->connectionsCache       = [];
            $this->connectionsCachedAtSec = time();
        } elseif ($collection = $this->connectionsCache[$collectionName] ?? null) {
            return $collection;
        }

        $filtered = $this->database->listCollectionNames([
            'filter' => [
                'name' => $collectionName,
            ],
        ]);

        if (iterator_count($filtered)) {
            return $this->connectionsCache[$collectionName] = $this->selectCollectionByName($collectionName);
        }

        try {
            $this->database->createCollection($collectionName);
        } catch (Throwable $exception) {
            if (!str_contains($exception->getMessage(), 'already exists')) {
                throw new RuntimeException(
                    message: $exception->getMessage(),
                    code: $exception->getCode(),
                    previous: $exception
                );
            }

            return $this->connectionsCache[$collectionName] = $this->selectCollectionByName($collectionName);
        }

        $collection = $this->selectCollectionByName($collectionName);

        $this->connectionsCache[$collectionName] = $collection;

        $indexFields = [
            'sid',
            'tid',
            'ptid',
            'tp',
            'st',
            'tgs.nm',
            'lat',
        ];

        try {
            foreach ($indexFields as $indexField) {
                $collection->createIndex([$indexField => 1]);
            }

            $collection->createIndex([
                'lat' => -1,
                '_id' => 1,
            ]);

            $collection->createIndex([
                'sid' => 1,
                'tid' => 1,
            ]);
        } catch (Throwable $exception) {
            if (!str_contains($exception->getMessage(), 'already exists')) {
                throw new RuntimeException(
                    message: $exception->getMessage(),
                    code: $exception->getCode(),
                    previous: $exception
                );
            }
        }

        $this->freshTraceTrees();

        return $collection;
    }

    /**
     * @param array<string, int> $index
     */
    public function createIndex(string $indexName, string $collectionName, array $index): void
    {
        $this->selectCollectionByName($collectionName)
            ->createIndex(
                keys: $index,
                name: $indexName,
            );
    }

    public function deleteIndex(string $collectionName, string $indexName): void
    {
        try {
            $this->selectCollectionByName($collectionName)
                ->dropIndex($indexName);
        } catch (Throwable) {
            // TODO
        }
    }

    /**
     * @param array<array<string, mixed>> $pipeline
     */
    public function aggregate(string $collectionName, array $pipeline): Iterator
    {
        return $this->selectCollectionByName($collectionName)
            ->aggregate($pipeline);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOne(string $collectionName, string $traceId): ?array
    {
        return $this->selectCollectionByName($collectionName)
            ->findOne(['tid' => $traceId]);
    }

    /**
     * @param string[] $traceIds
     *
     * @return array<array<string, mixed>>
     */
    public function findMany(string $collectionName, array $traceIds): array
    {
        return iterator_to_array(
            $this->selectCollectionByName($collectionName)
                ->findOne(['tid' => ['$in' => $traceIds]])
        );
    }

    public function findCollectionNameByTraceId(string $traceId): ?string
    {
        /** @var TraceTree|null $traceTree */
        $traceTree = TraceTree::query()->where('tid', $traceId)->first();

        return $traceTree?->__cn;
    }

    /**
     * @param string[] $traceIds
     */
    public function findCollectionNamesByTraceIds(array $traceIds): TraceCollectionNameObjects
    {
        $traceCollectionNames = new TraceCollectionNameObjects();

        $remainTraceIds = $traceIds;

        $collectionNames = $this->detectCollectionNamesReverse();

        foreach ($collectionNames as $collectionName) {
            $foundTraceIds = iterator_to_array(
                $this->selectCollectionByName($collectionName)
                    ->findOne(
                        filter: ['tid' => ['$in' => $remainTraceIds]],
                        projection: ['tid' => 1]
                    )
            );

            if (!count($foundTraceIds)) {
                continue;
            }

            $foundTraceIds = array_map(
                static fn(array $trace) => $trace['tid'],
                $foundTraceIds
            );

            $traceCollectionNames->add($collectionName, $foundTraceIds);

            $remainTraceIds = array_values(array_diff($remainTraceIds, $foundTraceIds));

            if (!count($remainTraceIds)) {
                break;
            }
        }

        return $traceCollectionNames;
    }

    /**
     * @template T
     *
     * @param string[]                                                           $collectionNames
     * @param array<array<string, mixed>>                                        $pipeline
     * @param Closure(string $collectionName, array<string, mixed> $document): T $documentPreparer
     *
     * @return T[]
     */
    public function paginate(
        array $collectionNames,
        array $pipeline,
        int $page,
        int $perPage,
        Closure $documentPreparer
    ): array {
        $result = [];

        $totalFoundCount   = 0;
        $resultTracesCount = 0;

        $offsetCount = ($page - 1) * $perPage;

        $collected = false;

        foreach ($collectionNames as $collectionName) {
            if ($collected) {
                break;
            }

            $collectionPage = 0;

            while (true) {
                if ($collected) {
                    break;
                }

                ++$collectionPage;

                $collectionPipeline = [
                    ...$pipeline,
                    [
                        '$skip' => ($collectionPage - 1) * $perPage,
                    ],
                    [
                        '$limit' => $perPage,
                    ],
                ];

                $cursor = $this->aggregate(
                    collectionName: $collectionName,
                    pipeline: $collectionPipeline
                );

                $documents = iterator_to_array($cursor);

                $documentsCount = count($documents);

                if (!$documentsCount) {
                    break;
                }

                $totalFoundCount += $documentsCount;

                if ($offsetCount >= $totalFoundCount) {
                    continue;
                }

                foreach ($documents as $document) {
                    $result[] = $documentPreparer($collectionName, $document);

                    if (++$resultTracesCount >= $perPage) {
                        $collected = true;

                        break;
                    }
                }
            }
        }

        return $result;
    }

    public function freshTraceTrees(): void
    {
        $collectionNames = $this->detectCollectionNames();

        if (!count($collectionNames)) {
            return;
        }

        $pipeline = [];

        $project = [
            'tid'  => 1,
            'ptid' => 1,
        ];

        $first = true;

        $mainCollectionName = null;

        foreach ($collectionNames as $collectionName) {
            $set = [
                '$set' => [
                    '__cn' => $collectionName,
                ],
            ];

            if ($first) {
                $mainCollectionName = $collectionName;

                $pipeline[] = [
                    '$project' => $project,
                ];

                $pipeline[] = $set;

                $first = false;

                continue;
            }

            $pipeline[] = [
                '$unionWith' => [
                    'coll'     => $collectionName,
                    'pipeline' => [
                        [
                            '$project' => $project,
                        ],
                        $set,
                    ],
                ],
            ];
        }

        $traceTreesCollectionName = new TraceTree()->getCollectionName();

        $exists = iterator_count($this->database->listCollectionNames([
                'filter' => [
                    'name' => $traceTreesCollectionName,
                ],
            ])) > 0;

        $operation = $exists ? 'collMod' : 'create';

        $this->database->command([
            $operation => $traceTreesCollectionName,
            'viewOn'   => $mainCollectionName,
            'pipeline' => $pipeline,
        ]);
    }
}
