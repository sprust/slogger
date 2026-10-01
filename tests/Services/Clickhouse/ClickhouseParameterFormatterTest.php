<?php

declare(strict_types=1);

namespace Tests\Services\Clickhouse;

use App\Services\Clickhouse\ClickhouseParameterFormatter;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class ClickhouseParameterFormatterTest extends TestCase
{
    /**
     * @return array<string, array{mixed, string}>
     */
    public static function valuesProvider(): array
    {
        return [
            'null'                     => [null, '\N'],
            'true'                     => [true, 'true'],
            'false'                    => [false, 'false'],
            'int'                      => [42, '42'],
            'float keeps its fraction' => [500.0, '500.0'],
            'float'                    => [0.137, '0.137'],
            'plain string'             => ['request', 'request'],
            'string escapes'           => ["a\\b\tc\nd'e", 'a\\\\b\\tc\\nd\'e'],
            'int array'                => [[1, 2, 3], '[1,2,3]'],
            'string array is quoted'   => [["x'y", 'b\\c'], "['x\\'y','b\\\\c']"],
            'empty array'              => [[], '[]'],
            'keys are dropped'         => [['a' => 'x', 'b' => 'y'], "['x','y']"],
        ];
    }

    #[DataProvider('valuesProvider')]
    public function testFormatsValue(mixed $value, string $expected): void
    {
        self::assertSame($expected, new ClickhouseParameterFormatter()->format($value));
    }

    public function testFormatsDateTimeInUtcWithMicroseconds(): void
    {
        $value = new DateTimeImmutable('2026-09-29 13:00:00.123456', new DateTimeZone('Europe/Moscow'));

        self::assertSame('2026-09-29 10:00:00.123456', new ClickhouseParameterFormatter()->format($value));
        self::assertSame("['2026-09-29 10:00:00.123456']", new ClickhouseParameterFormatter()->format([$value]));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function nonFiniteProvider(): array
    {
        return [
            'infinity'          => [INF],
            'negative infinity' => [-INF],
            'not a number'      => [NAN],
            'inside an array'   => [[1.5, INF]],
        ];
    }

    #[DataProvider('nonFiniteProvider')]
    public function testRefusesNonFiniteFloat(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ClickhouseParameterFormatter()->format($value);
    }

    public function testRefusesObject(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ClickhouseParameterFormatter()->format(new stdClass());
    }
}
