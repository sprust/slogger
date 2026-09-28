<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Repositories\Services\PeriodicTraceCollectionNameService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class PeriodicTraceCollectionNameServiceTest extends TestCase
{
    private const array COLLECTIONS = [
        'traces_2026_09_27_22_23',
        'traces_2026_09_27_23_24',
        'traces_2026_09_28_18_19',
        'traces_2026_09_28_19_20',
        'traces_2026_09_28_20_21',
        'traces_2026_09_28_21_22',
        'traceTreeCaches',
    ];

    public function testFromInsideAnHourSkipsTheHourBefore(): void
    {
        $this->assertSame(
            ['traces_2026_09_28_19_20', 'traces_2026_09_28_20_21'],
            $this->filter('2026-09-28 19:10:00', '2026-09-28 20:59:59')
        );
    }

    public function testFromOnAnHourBoundaryStartsWithThatHour(): void
    {
        $this->assertSame(
            ['traces_2026_09_28_19_20', 'traces_2026_09_28_20_21', 'traces_2026_09_28_21_22'],
            $this->filter('2026-09-28 19:00:00', null)
        );
    }

    public function testToKeepsItsOwnHour(): void
    {
        $this->assertSame(
            ['traces_2026_09_27_22_23', 'traces_2026_09_27_23_24', 'traces_2026_09_28_18_19', 'traces_2026_09_28_19_20'],
            $this->filter(null, '2026-09-28 19:00:00')
        );
    }

    public function testLastHourOfTheDay(): void
    {
        $this->assertSame(['traces_2026_09_27_23_24'], $this->filter('2026-09-27 23:30:00', '2026-09-27 23:59:59'));
    }

    public function testPeriodAcrossMidnight(): void
    {
        $this->assertSame(
            ['traces_2026_09_27_23_24', 'traces_2026_09_28_18_19'],
            $this->filter('2026-09-27 23:05:00', '2026-09-28 18:30:00')
        );
    }

    public function testWithoutBoundsAllTraceCollections(): void
    {
        $this->assertSame(array_slice(self::COLLECTIONS, 0, 6), $this->filter(null, null));
    }

    /**
     * @return string[]
     */
    private function filter(?string $from, ?string $to): array
    {
        return new PeriodicTraceCollectionNameService()->filterCollectionNamesByPeriod(
            collectionNames: self::COLLECTIONS,
            from: is_null($from) ? null : Carbon::parse($from, 'UTC'),
            to: is_null($to) ? null : Carbon::parse($to, 'UTC')
        );
    }
}
