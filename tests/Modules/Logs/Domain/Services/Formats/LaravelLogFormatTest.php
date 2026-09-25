<?php

namespace Tests\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Domain\Services\Formats\LaravelLogFormat;
use App\Modules\Logs\Domain\Services\Formats\LogTimeParser;
use App\Modules\Logs\Entities\Index\LogEntryStartObject;
use App\Modules\Logs\Enums\LaravelLogLevelEnum;
use PHPUnit\Framework\TestCase;

class LaravelLogFormatTest extends TestCase
{
    public function testAHeaderGivesTheTimeAndTheLevel(): void
    {
        $starts = $this->find("[2026-09-25 05:35:29] local.ERROR: connect refused {\"a\":1} []\n");

        $this->assertEquals(
            [new LogEntryStartObject(offset: 0, loggedAt: gmmktime(5, 35, 29, 9, 25, 2026), level: LaravelLogLevelEnum::Error->value)],
            $starts
        );
    }

    public function testAStackTraceBelongsToItsEntry(): void
    {
        $first  = "[2026-09-25 05:35:29] local.ERROR: boom {\"exception\":\"[object] ...\n[stacktrace]\n#0 /app/a.php(1): f()\n#1 {main}\n\"} \n";
        $second = "[2026-09-25 05:35:30] production.INFO: next\n";

        $starts = $this->find($first . $second);

        $this->assertSame([0, strlen($first)], array_map(static fn(LogEntryStartObject $start): int => $start->offset, $starts));
        $this->assertSame(LaravelLogLevelEnum::Info->value, $starts[1]->level);
    }

    public function testMicrosecondsAndAZoneAreRead(): void
    {
        $starts = $this->find("[2026-09-25T05:35:29.123456+03:00] local.WARNING: w\n");

        $this->assertSame(gmmktime(2, 35, 29, 9, 25, 2026), $starts[0]->loggedAt);
        $this->assertSame(LaravelLogLevelEnum::Warning->value, $starts[0]->level);
    }

    public function testEveryLevelIsKnown(): void
    {
        foreach (['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'] as $index => $level) {
            $starts = $this->find("[2026-09-25 05:35:29] local.$level: m\n");

            $this->assertSame($index + 1, $starts[0]->level, $level);
        }
    }

    public function testAnUnknownLevelIsZero(): void
    {
        $this->assertSame(0, $this->find("[2026-09-25 05:35:29] local.TRACE: m\n")[0]->level);
    }

    public function testAHeaderInsideALineIsNotAStart(): void
    {
        $this->assertSame([], $this->find("text [2026-09-25 05:35:29] local.ERROR: m\n"));
    }

    /**
     * @return list<LogEntryStartObject>
     */
    private function find(string $chunk): array
    {
        return new LaravelLogFormat(new LogTimeParser())->findEntryStarts($chunk);
    }
}
