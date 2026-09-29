<?php

namespace Tests\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Infrastructure\Tools\McpToolTimeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class McpToolTimeParserTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function validTimes(): array
    {
        return [
            'zulu'             => ['2026-09-28T20:00:00Z', '2026-09-28 20:00:00'],
            'offset'           => ['2026-09-28T23:00:00+03:00', '2026-09-28 20:00:00'],
            'offset no colon'  => ['2026-09-28T23:00:00+0300', '2026-09-28 20:00:00'],
            'fraction'         => ['2026-09-28T20:00:00.123Z', '2026-09-28 20:00:00'],
            'no seconds'       => ['2026-09-28T20:00Z', '2026-09-28 20:00:00'],
            'no zone is utc'   => ['2026-09-28T20:00:00', '2026-09-28 20:00:00'],
        ];
    }

    #[DataProvider('validTimes')]
    public function testParsesIso8601(string $value, string $expectedUtc): void
    {
        $this->assertSame($expectedUtc, new McpToolTimeParser()->parse($value)?->utc()->toDateTimeString());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidTimes(): array
    {
        return [
            'word'      => ['yesterday'],
            'date only' => ['2026-09-28'],
            'space'     => ['2026-09-28 20:00:00'],
            'bad month' => ['2026-13-28T20:00:00Z'],
        ];
    }

    #[DataProvider('invalidTimes')]
    public function testRejectsEverythingElse(string $value): void
    {
        $this->assertNull(new McpToolTimeParser()->parse($value));
    }
}
