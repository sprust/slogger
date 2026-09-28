<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Repositories\Services\PeriodicTraceCollectionNameService;
use App\Modules\Trace\Repositories\Services\TraceTimestampCollectionBatcher;
use App\Modules\Trace\Repositories\Services\TraceTimestampMetricsFactory;
use PHPUnit\Framework\TestCase;

class TraceTimestampCollectionBatcherTest extends TestCase
{
    public function testHourlyStepsPackCollectionsUpToTheBatchSize(): void
    {
        $collectionNames = $this->days(5);

        $batches = $this->batcher()->make($collectionNames, TraceTimestampEnum::Min);

        $this->assertSame(
            [array_slice($collectionNames, 0, 100), array_slice($collectionNames, 100)],
            $batches
        );
    }

    public function testBucketLongerThanAnHourStaysInOneBatch(): void
    {
        $collectionNames = [
            ...$this->hours('2026_09_24', 0, 23),
            ...$this->hours('2026_09_25', 0, 23),
            ...$this->hours('2026_09_26', 0, 23),
            ...$this->hours('2026_09_27', 0, 23),
            ...$this->hours('2026_09_28', 0, 23),
        ];

        $batches = $this->batcher()->make($collectionNames, TraceTimestampEnum::D);

        $this->assertSame(
            [
                [...$this->hours('2026_09_24', 0, 23), ...$this->hours('2026_09_25', 0, 23), ...$this->hours('2026_09_26', 0, 23), ...$this->hours('2026_09_27', 0, 23)],
                $this->hours('2026_09_28', 0, 23),
            ],
            $batches
        );
    }

    public function testFourHourBucketsAreNotSplit(): void
    {
        $batches = $this->batcher()->make($this->hours('2026_09_28', 18, 20), TraceTimestampEnum::H4);

        $this->assertSame([$this->hours('2026_09_28', 18, 20)], $batches);
    }

    public function testBucketBiggerThanTheBatchSizeIsOneBatch(): void
    {
        $collectionNames = $this->days(5);

        $this->assertSame([$collectionNames], $this->batcher()->make($collectionNames, TraceTimestampEnum::M));
    }

    public function testNoCollectionsNoBatches(): void
    {
        $this->assertSame([], $this->batcher()->make([], TraceTimestampEnum::H4));
    }

    /**
     * @return string[]
     */
    private function days(int $count): array
    {
        $collectionNames = [];

        foreach (range(1, $count) as $day) {
            $collectionNames = [...$collectionNames, ...$this->hours(sprintf('2026_09_%02d', $day), 0, 23)];
        }

        return $collectionNames;
    }

    /**
     * @return string[]
     */
    private function hours(string $day, int $from, int $to): array
    {
        return array_map(
            static fn(int $hour) => sprintf('traces_%s_%02d_%02d', $day, $hour, ($hour + 1) % 24),
            range($from, $to)
        );
    }

    private function batcher(): TraceTimestampCollectionBatcher
    {
        return new TraceTimestampCollectionBatcher(
            new PeriodicTraceCollectionNameService(),
            new TraceTimestampMetricsFactory()
        );
    }
}
