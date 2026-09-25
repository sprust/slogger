<?php

namespace Tests\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Domain\Services\Formats\LogTimeParser;
use App\Modules\Logs\Domain\Services\Formats\NginxErrorLogFormat;
use App\Modules\Logs\Entities\Index\LogEntryStartObject;
use App\Modules\Logs\Enums\NginxErrorLogLevelEnum;
use PHPUnit\Framework\TestCase;

class NginxErrorLogFormatTest extends TestCase
{
    public function testALineGivesTheTimeAndTheLevel(): void
    {
        $starts = $this->find(
            '2026/09/25 10:00:00 [error] 29#29: *1 connect() failed (111: Connection refused) while connecting to upstream, client: 172.18.0.1, server: , request: "GET / HTTP/1.1", upstream: "http://172.18.0.5:28080/", host: "localhost"' . "\n"
        );

        $this->assertEquals(
            [new LogEntryStartObject(offset: 0, loggedAt: gmmktime(10, 0, 0, 9, 25, 2026), level: NginxErrorLogLevelEnum::Error->value)],
            $starts
        );
    }

    public function testEveryLevelIsKnown(): void
    {
        foreach (['debug', 'info', 'notice', 'warn', 'error', 'crit', 'alert', 'emerg'] as $index => $level) {
            $starts = $this->find("2026/09/25 10:00:00 [$level] 1#1: m\n");

            $this->assertSame($index + 1, $starts[0]->level, $level);
        }
    }

    public function testALineThatDoesNotParseIsAnEntryWithoutLevelAndTime(): void
    {
        $first = "2026/09/25 10:00:00 [error] 1#1: m\n";

        $starts = $this->find($first . "nginx: [emerg] bad config\n");

        $this->assertCount(2, $starts);
        $this->assertEquals(new LogEntryStartObject(offset: strlen($first), loggedAt: null, level: 0), $starts[1]);
    }

    /**
     * @return list<LogEntryStartObject>
     */
    private function find(string $chunk): array
    {
        return new NginxErrorLogFormat(new LogTimeParser())->findEntryStarts($chunk);
    }
}
