<?php

namespace Tests\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Entities\Formats\LogLevelNameObject;
use App\Modules\Logs\Entities\Entry\LogEntryFieldObject;
use App\Modules\Logs\Domain\Services\Formats\LaravelLogFormat;
use App\Modules\Logs\Domain\Services\Formats\LogTimeParser;
use App\Modules\Logs\Domain\Services\Formats\NginxAccessLogFormat;
use App\Modules\Logs\Domain\Services\Formats\NginxErrorLogFormat;
use PHPUnit\Framework\TestCase;

class LogEntryParsingTest extends TestCase
{
    public function testALaravelEntryGivesTheMessageAndTheContext(): void
    {
        $details = $this->laravel()->parseEntry("[2026-09-25 05:35:30] production.INFO: worker started {\"worker\":1,\"tags\":[\"a\"]} \n");

        $this->assertSame('worker started', $details->message);
        $this->assertSame('{"worker":1,"tags":["a"]}', $details->context);
        $this->assertEquals([new LogEntryFieldObject(key: 'env', value: 'production')], $details->fields);
    }

    public function testAStackTraceInsideTheContextIsKept(): void
    {
        $text = "[2026-09-25 05:35:29] local.ERROR: connect: refused {\"exception\":\"[object] (E(code: 0): refused at /app/a.php:63)\n[stacktrace]\n#0 /app/a.php(1): f('x')\n#1 {main}\n\"} \n";

        $details = $this->laravel()->parseEntry($text);

        $this->assertSame('connect: refused', $details->message);
        $this->assertSame(
            "[object] (E(code: 0): refused at /app/a.php:63)\n[stacktrace]\n#0 /app/a.php(1): f('x')\n#1 {main}\n",
            json_decode((string) $details->context, true)['exception'] ?? null
        );
    }

    public function testEmptyContextAndExtraAreDropped(): void
    {
        $details = $this->laravel()->parseEntry("[2026-09-25 05:35:30] local.DEBUG: tick [] []\n");

        $this->assertSame('tick', $details->message);
        $this->assertNull($details->context);
    }

    public function testAMessageWithBracesButNoJsonHasNoContext(): void
    {
        $details = $this->laravel()->parseEntry("[2026-09-25 05:35:30] local.DEBUG: value {not json}\n");

        $this->assertSame('value {not json}', $details->message);
        $this->assertNull($details->context);
    }

    public function testAMultiLineMessageWithoutContextStaysWhole(): void
    {
        $details = $this->laravel()->parseEntry("[2026-09-25 05:35:30] local.DEBUG: first\nsecond\n");

        $this->assertSame("first\nsecond", $details->message);
    }

    public function testTextWithoutAHeaderIsTheMessage(): void
    {
        $this->assertSame('left over', $this->laravel()->parseEntry("left over\n")->message);
    }

    public function testAnAccessLineGivesItsFields(): void
    {
        $details = $this->access()->parseEntry(
            '172.18.0.1 - bob [25/Sep/2026:10:00:00 +0000] "GET /admin-api/logs?page=1 HTTP/1.1" 502 157 "http://localhost/" "Mozilla/5.0 (X11)"' . "\n"
        );

        $this->assertSame('GET /admin-api/logs?page=1 HTTP/1.1', $details->message);
        $this->assertSame(
            [
                'ip'         => '172.18.0.1',
                'user'       => 'bob',
                'method'     => 'GET',
                'path'       => '/admin-api/logs?page=1',
                'protocol'   => 'HTTP/1.1',
                'status'     => '502',
                'bytes'      => '157',
                'referer'    => 'http://localhost/',
                'user_agent' => 'Mozilla/5.0 (X11)',
            ],
            $this->fieldValues($details->fields)
        );
    }

    public function testDashesAndAMalformedRequestAreNulls(): void
    {
        $details = $this->access()->parseEntry('1.1.1.1 - - [25/Sep/2026:10:00:00 +0000] "\x16\x03" 400 0 "-" "-"');

        $fields = $this->fieldValues($details->fields);

        $this->assertNull($fields['user']);
        $this->assertNull($fields['method']);
        $this->assertNull($fields['referer']);
        $this->assertSame('400', $fields['status']);
    }

    public function testAnErrorLineGivesItsFields(): void
    {
        $details = $this->error()->parseEntry(
            '2026/09/25 10:00:00 [error] 29#30: *1 connect() failed (111: Connection refused) while connecting to upstream, client: 172.18.0.1, server: , request: "GET /a, b HTTP/1.1", upstream: "http://172.18.0.5:28080/a", host: "localhost"' . "\n"
        );

        $this->assertSame('connect() failed (111: Connection refused) while connecting to upstream', $details->message);
        $this->assertSame(
            [
                'pid'        => '29',
                'tid'        => '30',
                'connection' => '1',
                'client'     => '172.18.0.1',
                'server'     => '',
                'request'    => 'GET /a, b HTTP/1.1',
                'upstream'   => 'http://172.18.0.5:28080/a',
                'host'       => 'localhost',
                'referrer'   => null,
            ],
            $this->fieldValues($details->fields)
        );
    }

    public function testAnErrorLineWithoutDetailsIsTheMessage(): void
    {
        $details = $this->error()->parseEntry("2026/09/25 10:00:00 [notice] 1#1: signal process started\n");

        $this->assertSame('signal process started', $details->message);
        $this->assertNull($this->fieldValues($details->fields)['connection']);
    }

    public function testLevelNamesAreTheOnesTheLogsUse(): void
    {
        $this->assertContainsEquals(new LogLevelNameObject(level: 5, name: 'ERROR'), $this->laravel()->getLevelNames());
        $this->assertContainsEquals(new LogLevelNameObject(level: 5, name: '5xx'), $this->access()->getLevelNames());
        $this->assertContainsEquals(new LogLevelNameObject(level: 4, name: 'warn'), $this->error()->getLevelNames());
    }

    /**
     * @param list<LogEntryFieldObject> $fields
     *
     * @return array<string, string|null>
     */
    private function fieldValues(array $fields): array
    {
        $values = [];

        foreach ($fields as $field) {
            $values[$field->key] = $field->value;
        }

        return $values;
    }

    private function laravel(): LaravelLogFormat
    {
        return new LaravelLogFormat(new LogTimeParser());
    }

    private function access(): NginxAccessLogFormat
    {
        return new NginxAccessLogFormat(new LogTimeParser());
    }

    private function error(): NginxErrorLogFormat
    {
        return new NginxErrorLogFormat(new LogTimeParser());
    }
}
