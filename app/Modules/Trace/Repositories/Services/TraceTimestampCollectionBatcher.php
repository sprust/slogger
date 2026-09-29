<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Enums\TraceTimestampEnum;

readonly class TraceTimestampCollectionBatcher
{
    public const int BATCH_SIZE = 100;

    public function __construct(
        private PeriodicTraceCollectionNameService $collectionNameService,
        private TraceTimestampMetricsFactory $timestampMetricsFactory
    ) {
    }

    /**
     * @param string[] $collectionNames
     *
     * @return string[][]
     */
    public function make(array $collectionNames, TraceTimestampEnum $timestamp): array
    {
        /** @var string[][] $buckets */
        $buckets = [];

        foreach ($collectionNames as $collectionName) {
            $bucketKey = $this->timestampMetricsFactory
                ->prepareDateByTimestamp(
                    date: $this->collectionNameService->makeHourStart($collectionName),
                    timestamp: $timestamp
                )
                ->toDateTimeString();

            $buckets[$bucketKey][] = $collectionName;
        }

        /** @var string[][] $batches */
        $batches = [];
        /** @var string[] $batch */
        $batch = [];

        foreach ($buckets as $bucketCollectionNames) {
            if (count($batch) > 0 && count($batch) + count($bucketCollectionNames) > self::BATCH_SIZE) {
                $batches[] = $batch;
                $batch     = [];
            }

            $batch = [...$batch, ...$bucketCollectionNames];
        }

        if (count($batch) > 0) {
            $batches[] = $batch;
        }

        return $batches;
    }
}
