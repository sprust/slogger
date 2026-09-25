<?php

namespace Tests\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Domain\Services\Formats\LogTimeParser;
use App\Modules\Logs\Domain\Services\Formats\NginxAccessLogFormat;
use App\Modules\Logs\Entities\Index\LogEntryStartObject;
use App\Modules\Logs\Enums\HttpStatusClassEnum;
use PHPUnit\Framework\TestCase;

class NginxAccessLogFormatTest extends TestCase
{
    public function testACombinedLineGivesTheTimeAndTheStatusClass(): void
    {
        $starts = $this->find(
            '172.18.0.1 - - [25/Sep/2026:10:00:00 +0300] "GET /admin-api/logs?page=1 HTTP/1.1" 502 157 "http://localhost/" "Mozilla/5.0"' . "\n"
        );

        $this->assertEquals(
            [new LogEntryStartObject(offset: 0, loggedAt: gmmktime(7, 0, 0, 9, 25, 2026), level: HttpStatusClassEnum::ServerError->value)],
            $starts
        );
    }

    public function testEveryLineIsAnEntry(): void
    {
        $lines = [
            '1.1.1.1 - - [25/Sep/2026:10:00:00 +0000] "GET / HTTP/1.1" 200 1 "-" "-"',
            '1.1.1.1 - - [25/Sep/2026:10:00:01 +0000] "GET /a HTTP/1.1" 301 1 "-" "-"',
            '1.1.1.1 - bob [25/Sep/2026:10:00:02 +0000] "POST /b HTTP/1.1" 404 1 "-" "-"',
            '1.1.1.1 - - [25/Sep/2026:10:00:03 +0000] "GET /c HTTP/1.1" 101 0 "-" "-"',
        ];

        $starts = $this->find(implode("\n", $lines) . "\n");

        $this->assertSame(
            [
                HttpStatusClassEnum::Success->value,
                HttpStatusClassEnum::Redirection->value,
                HttpStatusClassEnum::ClientError->value,
                HttpStatusClassEnum::Informational->value,
            ],
            array_map(static fn(LogEntryStartObject $start): int => $start->level, $starts)
        );
        $this->assertSame(strlen($lines[0]) + 1, $starts[1]->offset);
    }

    public function testAnEscapedQuoteInTheRequestIsRead(): void
    {
        $starts = $this->find('1.1.1.1 - - [25/Sep/2026:10:00:00 +0000] "GET /\"x HTTP/1.1" 400 1 "-" "-"');

        $this->assertSame(HttpStatusClassEnum::ClientError->value, $starts[0]->level);
    }

    public function testALineThatDoesNotParseIsAnEntryWithoutLevelAndTime(): void
    {
        $starts = $this->find("garbage line\n");

        $this->assertEquals([new LogEntryStartObject(offset: 0, loggedAt: null, level: 0)], $starts);
    }

    public function testEmptyLinesAndTheEndOfTheChunkAreNotEntries(): void
    {
        $starts = $this->find("a\n\nb\n");

        $this->assertSame([0, 3], array_map(static fn(LogEntryStartObject $start): int => $start->offset, $starts));
    }

    public function testALastLineWithoutANewlineIsAnEntry(): void
    {
        $this->assertCount(2, $this->find("a\nb"));
    }

    /**
     * @return list<LogEntryStartObject>
     */
    private function find(string $chunk): array
    {
        return new NginxAccessLogFormat(new LogTimeParser())->findEntryStarts($chunk);
    }
}
