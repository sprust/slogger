<?php

namespace Tests\Modules\Dashboard\Repositories\Services;

use App\Modules\Dashboard\Entities\TraceMetricObject;
use App\Modules\Dashboard\Repositories\Services\TraceMetricReader;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use SConcur\Bson\UTCDateTime;

class TraceMetricReaderTest extends TestCase
{
    public function testADocumentReadsAsItsThreeCounters(): void
    {
        $metrics = new TraceMetricReader()->read([
            $this->document('http', '2026-09-11 12:05:00', logged: 3, buffered: 4, stored: 5),
        ]);

        $this->assertCount(1, $metrics);
        $this->assertSame('http', $metrics[0]->type);
        $this->assertSame('2026-09-11 12:05:00', $metrics[0]->timestamp->toDateTimeString());
        $this->assertSame([3, 4, 5], [$metrics[0]->logged, $metrics[0]->buffered, $metrics[0]->stored]);
    }

    public function testACounterNothingWasAddedToReadsAsZero(): void
    {
        $document = $this->document('http', '2026-09-11 12:05:00', logged: 0, buffered: 0, stored: 2);

        unset($document['lc'], $document['bc']);

        $metrics = new TraceMetricReader()->read([$document]);

        $this->assertSame([0, 0, 2], [$metrics[0]->logged, $metrics[0]->buffered, $metrics[0]->stored]);
    }

    public function testADocumentWithoutATypeOrASlotIsSkipped(): void
    {
        $withoutType = $this->document('http', '2026-09-11 12:05:00');
        unset($withoutType['tp']);

        $withoutSlot      = $this->document('http', '2026-09-11 12:05:00');
        $withoutSlot['t'] = '2026-09-11 12:05:00';

        $metrics = new TraceMetricReader()->read([
            'not a document',
            $withoutType,
            $withoutSlot,
            $this->document('job', '2026-09-11 12:10:00'),
        ]);

        $this->assertCount(1, $metrics);
        $this->assertSame('job', $metrics[0]->type);
    }

    public function testMetricsComeBackOldestFirstThenByType(): void
    {
        $metrics = new TraceMetricReader()->read([
            $this->document('job', '2026-09-11 12:10:00'),
            $this->document('job', '2026-09-11 12:05:00'),
            $this->document('http', '2026-09-11 12:05:00'),
        ]);

        $this->assertSame(
            ['12:05 http', '12:05 job', '12:10 job'],
            array_map(
                static fn(TraceMetricObject $metric): string => $metric->timestamp->format('H:i') . ' ' . $metric->type,
                $metrics
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function document(
        string $type,
        string $slot,
        int $logged = 1,
        int $buffered = 1,
        int $stored = 1
    ): array {
        return [
            'tp' => $type,
            't'  => new UTCDateTime(Carbon::parse($slot)),
            'lc' => $logged,
            'bc' => $buffered,
            'sc' => $stored,
        ];
    }
}
