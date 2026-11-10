<?php

namespace Tests\Modules\Logs\Domain\Services\Formats;

use RuntimeException;
use App\Modules\Logs\Domain\Services\Formats\LogTimeParser;
use App\Modules\Logs\Domain\Services\Formats\SconcurAccessLogFormat;
use App\Modules\Logs\Entities\Entry\LogEntryFieldObject;
use App\Modules\Logs\Entities\Index\LogEntryStartObject;
use App\Modules\Logs\Enums\HttpStatusClassEnum;
use PHPUnit\Framework\TestCase;

class SconcurAccessLogFormatTest extends TestCase
{
    public function testALineGivesTheTimeAndTheStatusClass(): void
    {
        $starts = $this->find("2026-10-28T11:36:40.037168 GET /admin-api/logs 504 15.45ms http#0 pid=22\n");

        $this->assertEquals(
            [
                new LogEntryStartObject(
                    offset: 0,
                    loggedAt: (gmmktime(11, 36, 40, 10, 28, 2026) ?: throw new RuntimeException('Invalid test timestamp')),
                    level: HttpStatusClassEnum::ServerError->value
                ),
            ],
            $starts
        );
    }

    public function testEveryLineIsAnEntry(): void
    {
        $lines = [
            '2026-10-28T11:36:41.036231 OPTIONS /a 204 1.19ms http#0 pid=22',
            '2026-10-28T11:36:41.039707 GET /b 302 20.19ms http#1 pid=23',
            '2026-10-28T11:36:42.035377 POST /c 422 0.96ms http#0 pid=22',
        ];

        $starts = $this->find(
            implode("\n", $lines)
                . "\n"
        );

        $this->assertSame(
            [
                HttpStatusClassEnum::Success->value,
                HttpStatusClassEnum::Redirection->value,
                HttpStatusClassEnum::ClientError->value,
            ],
            array_map(static fn(LogEntryStartObject $start): int => $start->level, $starts)
        );
        $this->assertSame(
            strlen($lines[0]) + 1,
            $starts[1]->offset
        );
    }

    public function testAnEntryIsIndexedAtTheEndOfTheRequest(): void
    {
        $starts = $this->find("2026-10-28T11:36:40.900000 GET / 200 150.00ms http#0 pid=22\n");

        $this->assertSame(
            (gmmktime(11, 36, 41, 10, 28, 2026) ?: throw new RuntimeException('Invalid test timestamp')),
            $starts[0]->loggedAt
        );
    }

    /** A slow request is written after the faster ones that started later. */
    public function testASlowRequestWrittenLateKeepsTheTimeOrder(): void
    {
        $starts = $this->find(
            "2026-10-28T12:00:05.000000 GET /fast 200 1.00ms http#1 pid=23\n"
                . "2026-10-28T12:00:00.000000 GET /slow 200 20000.00ms http#0 pid=22\n"
        );

        $this->assertSame(
            [(gmmktime(12, 0, 5, 10, 28, 2026) ?: throw new RuntimeException('Invalid test timestamp')), (gmmktime(12, 0, 20, 10, 28, 2026) ?: throw new RuntimeException('Invalid test timestamp'))],
            array_map(static fn(LogEntryStartObject $start): ?int => $start->loggedAt, $starts)
        );
    }

    public function testTheFieldsAreRead(): void
    {
        $details = $this
            ->format()
            ->parseEntry("2026-10-28T11:36:40.037168 GET /admin-api/logs?page=1 200 15.45ms http#1 pid=23\n");

        $this->assertSame(
            'GET /admin-api/logs?page=1',
            $details->message
        );
        $this->assertSame(
            [
                'method'      => 'GET',
                'path'        => '/admin-api/logs?page=1',
                'status'      => '200',
                'duration_ms' => '15.45',
                'worker'      => 'http#1',
                'pid'         => '23',
            ],
            $this->fields($details->fields)
        );
    }

    /** A worker writing to stdout leaves out its place in the pool. */
    public function testALineWithoutTheWorkerHasNoWorker(): void
    {
        $details = $this->format()->parseEntry('2026-10-28T11:36:40.037168 GET / 200 2.59ms');

        $this->assertNull(
            $this->fields($details->fields)['worker']
        );
        $this->assertNull(
            $this->fields($details->fields)['pid']
        );
    }

    public function testALabelWithoutASlotIsNotAWorker(): void
    {
        $line = '2026-10-28T11:36:40.037168 GET / 200 2.59ms other pid=22';

        $this->assertSame(
            HttpStatusClassEnum::Success->value,
            $this->find($line)[0]->level
        );
        $this->assertNull(
            $this->fields(
                $this->format()->parseEntry($line)->fields
            )['worker']
        );
    }

    public function testALineThatDoesNotParseIsAnEntryWithoutLevelAndTime(): void
    {
        $this->assertEquals(
            [new LogEntryStartObject(offset: 0, loggedAt: null, level: 0)],
            $this->find("garbage line\n")
        );
        $this->assertSame(
            'garbage line',
            $this->format()->parseEntry("garbage line\n")->message
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

    private function format(): SconcurAccessLogFormat
    {
        return new SconcurAccessLogFormat(
            new LogTimeParser()
        );
    }
}
