<?php

namespace Tests\Modules\Logs\Domain\Services\Reading;

use App\Modules\Logs\Domain\Services\Reading\LogIndexSearch;
use App\Modules\Logs\Entities\Index\LogIndexRecordObject;
use App\Modules\Logs\Repositories\LogIndexRepository;
use Tests\Modules\Logs\LogsTempDirTrait;
use Tests\TestCase;

class LogIndexSearchTest extends TestCase
{
    use LogsTempDirTrait;

    private const int COUNT = 3000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTempDir();

        $records = [];

        for ($index = 0; $index < self::COUNT; ++$index) {
            $records[] = new LogIndexRecordObject(
                entryNo: $index * 3,
                offset: $index,
                length: 1,
                loggedAt: 1000 + intdiv($index, 2),
                level: 1
            );
        }

        $this->app->make(LogIndexRepository::class)->appendRecords('f', $records);
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();

        parent::tearDown();
    }

    public function testTheLowerBoundByEntryNoIsTheFirstRecordAtOrAbove(): void
    {
        $search = $this->app->make(LogIndexSearch::class);

        $this->assertSame(0, $search->lowerBoundByEntryNo('f', 1, self::COUNT, -5));
        $this->assertSame(0, $search->lowerBoundByEntryNo('f', 1, self::COUNT, 0));
        $this->assertSame(1, $search->lowerBoundByEntryNo('f', 1, self::COUNT, 1));
        $this->assertSame(1234, $search->lowerBoundByEntryNo('f', 1, self::COUNT, 3702));
        $this->assertSame(1235, $search->lowerBoundByEntryNo('f', 1, self::COUNT, 3703));
        $this->assertSame(self::COUNT, $search->lowerBoundByEntryNo('f', 1, self::COUNT, 100_000));
    }

    public function testTheLowerBoundByTimeIsTheFirstRecordAtOrAfter(): void
    {
        $search = $this->app->make(LogIndexSearch::class);

        $this->assertSame(0, $search->lowerBoundByTime('f', self::COUNT, 0));
        $this->assertSame(2, $search->lowerBoundByTime('f', self::COUNT, 1001));
        $this->assertSame(2468, $search->lowerBoundByTime('f', self::COUNT, 2234));
        $this->assertSame(self::COUNT, $search->lowerBoundByTime('f', self::COUNT, 99_999));
    }

    public function testAnEmptyIndexHasItsBoundAtZero(): void
    {
        $this->assertSame(0, $this->app->make(LogIndexSearch::class)->lowerBoundByTime('missing', 0, 10));
    }
}
