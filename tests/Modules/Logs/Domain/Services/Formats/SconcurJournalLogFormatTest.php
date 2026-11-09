<?php

namespace Tests\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Domain\Services\Formats\LogTimeParser;
use App\Modules\Logs\Domain\Services\Formats\SconcurJournalLogFormat;
use App\Modules\Logs\Entities\Entry\LogEntryFieldObject;
use App\Modules\Logs\Entities\Index\LogEntryStartObject;
use App\Modules\Logs\Enums\SconcurJournalLogLevelEnum;
use PHPUnit\Framework\TestCase;

class SconcurJournalLogFormatTest extends TestCase
{
    public function testALineGivesTheTimeAndTheLevel(): void
    {
        $starts = $this->find("[2026-10-28 06:30:06.060236] ERROR [worker: 12 rabbitmq #0]: exited signal=11 uptime=0.7s; restarting in 200ms []\n");

        $this->assertEquals(
            [
                new LogEntryStartObject(
                    offset: 0,
                    loggedAt: gmmktime(6, 30, 6, 10, 28, 2026),
                    level: SconcurJournalLogLevelEnum::Error->value
                ),
            ],
            $starts
        );
    }

    public function testEveryLevelIsKnown(): void
    {
        foreach (['INFO', 'WARN', 'ERROR'] as $index => $level) {
            $starts = $this->find("[2026-10-28 06:30:05.339951] $level [master: 7]: m []\n");

            $this->assertSame($index + 1, $starts[0]->level, $level);
        }
    }

    public function testAMasterLineHasNoWorker(): void
    {
        $details = $this->format()->parseEntry("[2026-10-28 06:30:05.339951] INFO [master: 7]: start groups=4 workers=5 []\n");

        $this->assertSame('start groups=4 workers=5', $details->message);
        $this->assertNull($details->context);
        $this->assertSame(
            ['process' => 'master', 'pid' => '7', 'worker' => null],
            $this->fields($details->fields)
        );
    }

    public function testAWorkerLineNamesTheWorker(): void
    {
        $details = $this->format()->parseEntry('[2026-10-28 06:30:05.353636] INFO [worker: 10 http #1]: spawned []');

        $this->assertSame('spawned', $details->message);
        $this->assertSame(
            ['process' => 'worker', 'pid' => '10', 'worker' => 'http#1'],
            $this->fields($details->fields)
        );
    }

    public function testAWorkerOutsideAGroupIsItsSlot(): void
    {
        $details = $this->format()->parseEntry('[2026-10-28 06:30:05.353636] ERROR [worker: 12 #0]: PHP Fatal error []');

        $this->assertSame('PHP Fatal error', $details->message);
        $this->assertSame(
            ['process' => 'worker', 'pid' => '12', 'worker' => '#0'],
            $this->fields($details->fields)
        );
    }

    public function testAContextIsSplitFromTheMessage(): void
    {
        $details = $this->format()->parseEntry('[2026-10-28 06:30:05.353636] WARN [master: 7]: a {b} c {"group":"http"}');

        $this->assertSame('a {b} c', $details->message);
        $this->assertSame('{"group":"http"}', $details->context);
    }

    public function testALineThatDoesNotParseIsAnEntryWithoutLevelAndTime(): void
    {
        $this->assertEquals(
            [new LogEntryStartObject(offset: 0, loggedAt: null, level: 0)],
            $this->find("PHP Fatal error\n")
        );
        $this->assertSame(
            'PHP Fatal error',
            $this->format()->parseEntry("PHP Fatal error\n")->message
        );
    }

    /**
     * @param list<LogEntryFieldObject> $fields
     *
     * @return array<string, ?string>
     */
    private function fields(array $fields): array
    {
        $values = [];

        foreach ($fields as $field) {
            $values[$field->key] = $field->value;
        }

        return $values;
    }

    /**
     * @return list<LogEntryStartObject>
     */
    private function find(string $chunk): array
    {
        return $this->format()->findEntryStarts($chunk);
    }

    private function format(): SconcurJournalLogFormat
    {
        return new SconcurJournalLogFormat(new LogTimeParser());
    }
}
