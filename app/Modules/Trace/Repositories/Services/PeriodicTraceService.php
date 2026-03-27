<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Services;

use App\Models\Traces\TraceTree;
use App\Modules\Trace\Entities\Trace\TraceCollectionNameObjects;
use Closure;
use Illuminate\Support\Carbon;
use Iterator;
use MongoDB\Database;
use SConcur\Features\Mongodb\Connection\Collection;
use SConcur\Features\Mongodb\Connection\Database as SconcurDatabase;
use Throwable;

readonly class PeriodicTraceService
{
    public function __construct(
        private Database $database,
        private SconcurDatabase $sconcurDatabase,
        private PeriodicTraceCollectionNameService $periodicTraceCollectionNameService
    ) {
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
        $iterator = $this->selectCollectionByName($collectionName)
            ->aggregate([
                [
                    '$match' => [
                        'tid' => [
                            '$in' => $traceIds,
                        ],
                    ],
                ],
            ]);

        $traces = [];

        foreach ($iterator as $item) {
            $traces[] = $item;
        }

        return $traces;
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
            $iterator = $this->selectCollectionByName($collectionName)
                ->aggregate([
                    [
                        '$project' => ['tid' => 1],
                    ],
                    [
                        '$match' => ['tid' => ['$in' => $remainTraceIds]],
                    ],
                ]);

            $foundTraceIds = [];

            foreach ($iterator as $item) {
                $foundTraceIds[] = $item['tid'];
            }

            if (!count($foundTraceIds)) {
                continue;
            }

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

                $documents = [];

                foreach ($cursor as $item) {
                    $documents[] = $item;
                }

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
}
