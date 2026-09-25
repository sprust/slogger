<?php

namespace Tests\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Domain\Services\Formats\LogTimeParser;
use App\Modules\Logs\Domain\Services\Formats\ReceiverLogFormat;
use App\Modules\Logs\Entities\Index\LogEntryStartObject;
use App\Modules\Logs\Enums\ReceiverLogLevelEnum;
use PHPUnit\Framework\TestCase;

class ReceiverLogFormatTest extends TestCase
{
    public function testAHeaderGivesTheTimeAndTheLevel(): void
    {
        $starts = $this->find("2026-09-25 07:00:57.401 WARN Removed old log file\n");

        $this->assertEquals(
            [new LogEntryStartObject(offset: 0, loggedAt: gmmktime(7, 0, 57, 9, 25, 2026), level: ReceiverLogLevelEnum::Warn->value)],
            $starts
        );
    }

    public function testAStackTraceBelongsToItsEntry(): void
    {
        $first  = "2026-09-25 07:00:57.401 ERROR dial tcp: connection refused\n->stack trace:\n - /app/internal/a.go:140\n<-end of trace\n";
        $second = "2026-09-25 07:01:08.585 DEBUG received message with len 944\n";

        $starts = $this->find($first . $second);

        $this->assertSame([0, strlen($first)], array_map(static fn(LogEntryStartObject $start): int => $start->offset, $starts));
        $this->assertSame(ReceiverLogLevelEnum::Error->value, $starts[0]->level);
        $this->assertSame(ReceiverLogLevelEnum::Debug->value, $starts[1]->level);
    }

    public function testEveryLevelIsKnown(): void
    {
        foreach (['DEBUG', 'INFO', 'WARN', 'ERROR'] as $index => $level) {
            $starts = $this->find("2026-09-25 07:00:57.401 $level m\n");

            $this->assertSame($index + 1, $starts[0]->level, $level);
        }
    }

    public function testAnUnknownLevelIsZero(): void
    {
        $this->assertSame(0, $this->find("2026-09-25 07:00:57.401 TRACE m\n")[0]->level);
    }

    public function testATraceLineIsNotAStart(): void
    {
        $this->assertSame([], $this->find(" - /app/internal/a.go:140\n<-end of trace\n"));
    }

    public function testTheMessageIsTheFirstLine(): void
    {
        $details = $this->format()->parseEntry(
            "2026-09-25 07:00:57.401 ERROR dial tcp: connection refused\n->stack trace:\n - /app/internal/a.go:140\n<-end of trace\n"
        );

        $this->assertSame('dial tcp: connection refused', $details->message);
        $this->assertNull($details->context);
        $this->assertSame([], $details->fields);
    }

    public function testTextWithoutAHeaderIsTheMessage(): void
    {
        $this->assertSame('<-end of trace', $this->format()->parseEntry("<-end of trace\n")->message);
    }

    /**
     * @return list<LogEntryStartObject>
     */
    private function find(string $chunk): array
    {
        return $this->format()->findEntryStarts($chunk);
    }

    private function format(): ReceiverLogFormat
    {
        return new ReceiverLogFormat(new LogTimeParser());
    }
}
