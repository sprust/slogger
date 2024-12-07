<?php

namespace App\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Repositories\Dto\PeriodicTraceAggregationDto;
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
    public function detectCollectionNames(?Carbon $loggedAtFrom = null, ?Carbon $loggedAtTo = null): array
    {
        $allCollectionNames = Arr::sort(
            iterator_to_array($this->database->listCollectionNames())
        );

        if (!$loggedAtFrom && !$loggedAtTo) {
            return $allCollectionNames;
        }

        if (!$loggedAtFrom) {
            $indexFrom = 0;
        } else {
            $indexFrom = (int) array_search(
                needle: $this->makeCollectionName($loggedAtFrom),
                haystack: $allCollectionNames,
                strict: true
            ) ?: 0;
        }

        if (!$loggedAtTo) {
            $indexTo = count($allCollectionNames) - 1;
        } else {
            $indexTo = (int) array_search(
                needle: $this->makeCollectionName($loggedAtTo),
                haystack: $allCollectionNames,
                strict: true
            ) ?: (count($allCollectionNames) - 1);
        }

        return array_slice(
            $allCollectionNames,
            $indexFrom,
            $indexTo - $indexFrom + 1
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
            return $this->collections[$collectionName] = $this->database->selectCollection($collectionName);
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

            return $this->collections[$collectionName] = $this->database->selectCollection($collectionName);
        }

        $collection = $this->database->selectCollection($collectionName);

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
            } catch (Throwable) {
                break;
            }
        }

        return $collection;
    }

    public function existsTrace(string $collectionName, string $traceId): bool
    {
        return $this->database->selectCollection($collectionName)
                ->countDocuments(['tid' => $traceId], ['limit' => 1]) > 0;
    }

    public function createIndex(string $indexName, string $collectionName, array $index): void
    {
        $this->database->selectCollection($collectionName)
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
            $this->database->selectCollection($collectionName)
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
     * @param array<string, mixed> $match
     */
    public function makeAggregation(
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $match = []
    ): ?PeriodicTraceAggregationDto {
        $collectionNames = $this->detectCollectionNames(
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo
        );

        if (!count($collectionNames)) {
            return null;
        }

        $hasMatch = count($match) > 0;

        $pipeline = [];

        $first = true;

        foreach ($collectionNames as $collectionName) {
            if ($first) {
                if ($hasMatch) {
                    $pipeline[] = [
                        '$match' => $match,
                    ];
                }

                $first = false;

                continue;
            }

            $pipeline[] = [
                '$unionWith' => [
                    'coll' => $collectionName,
                    ...($hasMatch ? ['pipeline' => [['$match' => $match]]] : []),
                ],
            ];
        }

        return new PeriodicTraceAggregationDto(
            collectionName: $collectionNames[0],
            pipeline: $pipeline
        );
    }

    /**
     * @param array<array<string, mixed>> $pipeline
     */
    public function aggregate(string $collectionName, array $pipeline): CursorInterface&Iterator
    {
        return $this->database->selectCollection($collectionName)
            ->aggregate($pipeline);
    }
}
