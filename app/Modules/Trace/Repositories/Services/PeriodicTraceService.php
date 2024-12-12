<?php

namespace App\Modules\Trace\Repositories\Services;

use App\Models\Traces\TraceTree;
use App\Modules\Trace\Entities\Trace\TraceCollectionNameObjects;
use App\Modules\Trace\Repositories\Events\TraceCollectionCreatedEvent;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Iterator;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\CursorInterface;
use RuntimeException;
use Throwable;

class PeriodicTraceService
{
    private int $hoursStep = 4;

    /** @var array<string, Collection> */
    private array $collections = [];

    public function __construct(private readonly Database $database)
    {
    }

    public function selectCollectionByName(string $collectionName): Collection
    {
        return $this->database->selectCollection($collectionName);
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
        $allCollectionNames = array_values(
            Arr::sort(
                array_filter(
                    iterator_to_array($this->database->listCollectionNames()),
                    static fn(string $collectionName) => str_starts_with($collectionName, 'traces_')
                )
            )
        );

        $allCollectionNamesCount = count($allCollectionNames);

        if (!$allCollectionNamesCount) {
            return [];
        }

        if (!$loggedAtFrom && !$loggedAtTo) {
            return $allCollectionNames;
        }

        $loggedAtFromCollectionName = $loggedAtFrom
            ? $this->makeCollectionName($loggedAtFrom)
            : $allCollectionNames[0];

        $loggedAtToCollectionName = $loggedAtTo
            ? $this->makeCollectionName($loggedAtTo)
            : $allCollectionNames[$allCollectionNamesCount - 1];

        return array_values(
            array_filter(
                $allCollectionNames,
                static function (string $collectionName) use ($loggedAtFromCollectionName, $loggedAtToCollectionName) {
                    if ($loggedAtFromCollectionName && $collectionName < $loggedAtFromCollectionName) {
                        return false;
                    }

                    if ($loggedAtToCollectionName && $collectionName > $loggedAtToCollectionName) {
                        return false;
                    }

                    return true;
                }
            )
        );
    }

    public function initCollection(Carbon $loggedAt): Collection
    {
        $collectionName = $this->makeCollectionName($loggedAt);

        if ($collection = $this->collections[$collectionName] ?? null) {
            return $collection;
        }

        $filtered = $this->database->listCollectionNames([
            'filter' => [
                'name' => $collectionName,
            ],
        ]);

        if (iterator_count($filtered)) {
            return $this->collections[$collectionName] = $this->selectCollectionByName($collectionName);
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

            return $this->collections[$collectionName] = $this->selectCollectionByName($collectionName);
        }

        $collection = $this->selectCollectionByName($collectionName);

        $this->collections[$collectionName] = $collection;

        $indexFields = [
            'sid',
            'tid',
            'ptid',
            'tp',
            'st',
            'tgs.nm',
            'lat',
        ];

        foreach ($indexFields as $indexField) {
            try {
                $collection->createIndex([$indexField => 1]);
            } catch (Throwable $exception) {
                if (!str_contains($exception->getMessage(), 'already exists')) {
                    throw new RuntimeException(
                        message: $exception->getMessage(),
                        code: $exception->getCode(),
                        previous: $exception
                    );
                }
            }
        }

        $collection->createIndex([
            'lat' => -1,
            '_id' => 1,
        ]);

        event(new TraceCollectionCreatedEvent($collectionName));

        return $collection;
    }

    public function createIndex(string $indexName, string $collectionName, array $index): void
    {
        $this->selectCollectionByName($collectionName)
            ->createIndex(
                key: $index,
                options: [
                    'name' => $indexName,
                ]
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

    public function makeCollectionName(Carbon $loggedAt): string
    {
        $date = $loggedAt->format('Y_m_d');

        $hourFrom = (int) floor($loggedAt->hour / $this->hoursStep) * $this->hoursStep;
        $hourTo   = $hourFrom + $this->hoursStep - 1;

        $hourFromFormatted = sprintf('%02d', $hourFrom);
        $hourToFormatted   = sprintf('%02d', $hourTo);

        return "traces_{$date}_{$hourFromFormatted}_$hourToFormatted";
    }

    /**
     * @param array<array<string, mixed>> $pipeline
     */
    public function aggregate(string $collectionName, array $pipeline): CursorInterface&Iterator
    {
        return $this->selectCollectionByName($collectionName)
            ->aggregate($pipeline);
    }

    public function findOne(string $collectionName, string $traceId): ?array
    {
        return $this->selectCollectionByName($collectionName)
            ->findOne(['tid' => $traceId]);
    }

    /**
     * @param string[] $traceIds
     *
     * @return array[]
     */
    public function findMany(string $collectionName, array $traceIds): array
    {
        return iterator_to_array(
            $this->selectCollectionByName($collectionName)
                ->find(['tid' => ['$in' => $traceIds]])
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
                    ->find(
                        ['tid' => ['$in' => $remainTraceIds]],
                        ['projection' => ['tid' => 1]]
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
     * @param string[]                                            $collectionNames
     * @param array<array<string, mixed>>                         $pipeline
     * @param Closure(string $collectionName, array $document): T $documentPreparer
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

        $traceTreesCollectionName = (new TraceTree())->getCollectionName();

        $exists = iterator_count($this->database->listCollectionNames([
                'filter' => [
                    'name' => $traceTreesCollectionName,
                ],
            ])) > 0;

        if ($exists) {
            $this->selectCollectionByName($traceTreesCollectionName)->drop();
        }

        $this->database->command([
            'create'   => $traceTreesCollectionName,
            'viewOn'   => $mainCollectionName,
            'pipeline' => $pipeline,
        ]);
    }
}