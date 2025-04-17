<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Modules\Trace\Contracts\Repositories\TraceContentRepositoryInterface;
use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Repositories\Services\PeriodicTraceService;
use App\Modules\Trace\Repositories\Services\TracePipelineBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use RuntimeException;
use SParallel\Exceptions\SParallelTimeoutException;
use SParallel\Objects\ResultErrorObject;
use SParallel\Services\SParallelService;

readonly class TraceContentRepository implements TraceContentRepositoryInterface
{
    public function __construct(
        private SParallelService $parallelService,
        private TracePipelineBuilder $tracePipelineBuilder,
        private PeriodicTraceService $periodicTraceService
    ) {
    }

    /**
     * @throws SParallelTimeoutException
     */
    public function findTypes(
        array $serviceIds = [],
        ?string $text = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterParameters $data = null,
        ?bool $hasProfiling = null,
    ): array {
        $collectionNames = $this->periodicTraceService->detectCollectionNamesReverse(
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
        );

        if (!$collectionNames) {
            return [];
        }

        $pipeline = $this->tracePipelineBuilder->make(
            serviceIds: $serviceIds,
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            durationFrom: $durationFrom,
            durationTo: $durationTo,
            memoryFrom: $memoryFrom,
            memoryTo: $memoryTo,
            cpuFrom: $cpuFrom,
            cpuTo: $cpuTo,
            data: $data,
            hasProfiling: $hasProfiling,
            projectFields: [
                'tp',
            ],
            customMatch: $text
                ? [
                    'tp' => [
                        '$regex' => "^.*$text.*$",
                    ],
                ]
                : null
        );

        $pipeline[] = [
            '$sort' => [
                'lat' => -1,
            ],
        ];

        $pipeline[] = [
            '$group' => [
                '_id'   => '$tp',
                'count' => [
                    '$sum' => 1,
                ],
            ],
        ];

        return $this->handleRequest(
            collectionNames: $collectionNames,
            pipeline: $pipeline
        );
    }

    /**
     * @throws SParallelTimeoutException
     */
    public function findTags(
        array $serviceIds = [],
        ?string $text = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterParameters $data = null,
        ?bool $hasProfiling = null,
    ): array {
        $collectionNames = $this->periodicTraceService->detectCollectionNamesReverse(
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
        );

        if (!$collectionNames) {
            return [];
        }

        $pipeline = $this->tracePipelineBuilder->make(
            serviceIds: $serviceIds,
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            types: $types,
            durationFrom: $durationFrom,
            durationTo: $durationTo,
            memoryFrom: $memoryFrom,
            memoryTo: $memoryTo,
            cpuFrom: $cpuFrom,
            cpuTo: $cpuTo,
            data: $data,
            hasProfiling: $hasProfiling,
            projectFields: [
                'tgs',
            ],
        );

        $pipeline[] = [
            '$sort' => [
                'lat' => -1,
            ],
        ];

        $pipeline[] = [
            '$limit' => 100000,
        ];

        $pipeline[] = [
            '$unwind' => [
                'path' => '$tgs',
            ],
        ];

        $pipeline[] = [
            '$group' => [
                '_id'   => '$tgs.nm',
                'count' => [
                    '$sum' => 1,
                ],
            ],
        ];

        if ($text) {
            $pipeline[] = [
                '$match' => [
                    '_id' => [
                        '$regex' => "^.*$text.*$",
                    ],
                ],
            ];
        }

        $pipeline[] = [
            '$sort' => [
                'count' => -1,
                '_id'   => 1,
            ],
        ];

        $pipeline[] = [
            '$limit' => 50,
        ];

        return $this->handleRequest(
            collectionNames: $collectionNames,
            pipeline: $pipeline
        );
    }

    /**
     * @throws SParallelTimeoutException
     */
    public function findStatuses(
        array $serviceIds = [],
        ?string $text = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $tags = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterParameters $data = null,
        ?bool $hasProfiling = null,
    ): array {
        $collectionNames = $this->periodicTraceService->detectCollectionNamesReverse(
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
        );

        if (!$collectionNames) {
            return [];
        }

        $pipeline = $this->tracePipelineBuilder->make(
            serviceIds: $serviceIds,
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            types: $types,
            tags: $tags,
            durationFrom: $durationFrom,
            durationTo: $durationTo,
            memoryFrom: $memoryFrom,
            memoryTo: $memoryTo,
            cpuFrom: $cpuFrom,
            cpuTo: $cpuTo,
            data: $data,
            hasProfiling: $hasProfiling,
            projectFields: [
                'st',
            ],
            customMatch: $text
                ? [
                    'st' => [
                        '$regex' => "^.*$text.*$",
                    ],
                ]
                : null
        );

        $pipeline[] = [
            '$sort' => [
                'lat' => -1,
            ],
        ];

        $pipeline[] = [
            '$group' => [
                '_id'   => '$st',
                'count' => [
                    '$sum' => 1,
                ],
            ],
        ];

        return $this->handleRequest(
            collectionNames: $collectionNames,
            pipeline: $pipeline
        );
    }

    /**
     * @param string[]                    $collectionNames
     * @param array<array<string, mixed>> $pipeline
     *
     * @return TraceStringFieldObject[]
     *
     * @throws SParallelTimeoutException
     */
    private function handleRequest(array $collectionNames, array $pipeline): array
    {
        /** @var callable[] $callbacks */
        $callbacks = [];

        foreach ($collectionNames as $collectionName) {
            $callbacks[] = static function () use ($collectionName, $pipeline) {
                $periodicTraceService = app(PeriodicTraceService::class);

                /** @var array<string, int> $groups */
                $groups = [];

                $cursor = $periodicTraceService->aggregate(
                    collectionName: $collectionName,
                    pipeline: $pipeline
                );

                foreach ($cursor as $item) {
                    $id = $item['_id'];

                    $groups[$id] ??= 0;
                    $groups[$id] += $item['count'];
                }

                return $groups;
            };
        }

        $results = $this->parallelService->wait(
            callbacks: $callbacks,
            waitMicroseconds: 20_000_000,
            breakAtFirstError: true
        );

        // TODO: normal error handling
        if ($results->hasFailed()) {
            /** @var ResultErrorObject|null $resultError */
            $resultError = ($results->getFailed()[0] ?? null)->error;

            if (!$resultError) {
                throw new RuntimeException('Unknown error');
            }

            throw new RuntimeException(
                $resultError->message . PHP_EOL . $resultError->traceAsString
            );
        }

        /** @var array<string|int, int> $groups */
        $groups = [];

        foreach ($results->getResults() as $result) {
            foreach ($result->result as $type => $count) {
                $groups[$type] ??= 0;
                $groups[$type] += $count;
            }
        }

        $limit = 50;

        /** @var TraceStringFieldObject[] $result */
        $result = [];

        foreach ($groups as $name => $count) {
            $result[] = new TraceStringFieldObject(
                name: (string) $name,
                count: $count
            );
        }

        $sortedResult = Arr::sortDesc(
            $result,
            fn(TraceStringFieldObject $item) => $item->count
        );

        return array_slice($sortedResult, 0, $limit);
    }
}
