<?php

namespace Tests\Modules\Mcp\Domain\Services;

use App\Modules\Mcp\Domain\Exceptions\McpTraceDataFilterInvalidException;
use App\Modules\Mcp\Domain\Services\McpTraceDataFilterParser;
use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterItemParameters;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class McpTraceDataFilterParserTest extends TestCase
{
    /**
     * @return array<string, array{string, TraceDataFilterCompNumericTypeEnum, int|float}>
     */
    public static function numericConditions(): array
    {
        return [
            'gte'    => ['response.status >= 500', TraceDataFilterCompNumericTypeEnum::Gte, 500],
            'lte'    => ['response.status<=499', TraceDataFilterCompNumericTypeEnum::Lte, 499],
            'gt'     => ['time > 0.5', TraceDataFilterCompNumericTypeEnum::Gt, 0.5],
            'lt'     => ['time < -1', TraceDataFilterCompNumericTypeEnum::Lt, -1],
            'eq'     => ['user.id = 42', TraceDataFilterCompNumericTypeEnum::Eq, 42],
            'neq'    => ['user.id != 42', TraceDataFilterCompNumericTypeEnum::Neq, 42],
        ];
    }

    #[DataProvider('numericConditions')]
    public function testNumeric(string $condition, TraceDataFilterCompNumericTypeEnum $comp, int|float $value): void
    {
        $item = $this->parseOne($condition);

        $this->assertSame($comp, $item->numeric?->comp);
        $this->assertSame($value, $item->numeric->value);
        $this->assertNull($item->string);
        $this->assertNull($item->boolean);
    }

    public function testKeyGetsDataPrefix(): void
    {
        $this->assertSame('dt.response.status', $this->parseOne('response.status >= 500')->field);
        $this->assertSame('dt.dt.x', $this->parseOne('dt.x exists')->field);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function booleanConditions(): array
    {
        return [
            'eq true'   => ['cache.hit = true', true],
            'eq false'  => ['cache.hit = false', false],
            'neq true'  => ['cache.hit != true', false],
            'neq false' => ['cache.hit != false', true],
        ];
    }

    #[DataProvider('booleanConditions')]
    public function testBoolean(string $condition, bool $value): void
    {
        $this->assertSame($value, $this->parseOne($condition)->boolean?->value);
    }

    /**
     * @return array<string, array{string, TraceDataFilterCompStringTypeEnum, string}>
     */
    public static function stringConditions(): array
    {
        return [
            'eq'       => ['method = "GET"', TraceDataFilterCompStringTypeEnum::Eq, 'GET'],
            'neq'      => ['method != "GET"', TraceDataFilterCompStringTypeEnum::Neq, 'GET'],
            'contains' => ['request.uri contains "/api/v1 users"', TraceDataFilterCompStringTypeEnum::Con, '/api/v1 users'],
            'starts'   => ['request.uri starts "/api"', TraceDataFilterCompStringTypeEnum::Starts, '/api'],
            'ends'     => ['request.uri ends ".json"', TraceDataFilterCompStringTypeEnum::Ends, '.json'],
            'number'   => ['code = "500"', TraceDataFilterCompStringTypeEnum::Eq, '500'],
        ];
    }

    #[DataProvider('stringConditions')]
    public function testString(string $condition, TraceDataFilterCompStringTypeEnum $comp, string $value): void
    {
        $item = $this->parseOne($condition);

        $this->assertSame($comp, $item->string?->comp);
        $this->assertSame($value, $item->string->value);
    }

    /**
     * @return array<string, array{string, bool|null, bool|null}>
     */
    public static function presenceConditions(): array
    {
        return [
            'exists'      => ['user.id exists', true, null],
            'missing'     => ['user.id missing', false, null],
            'is null'     => ['user.id is null', null, true],
            'is not null' => ['user.id is not null', null, false],
        ];
    }

    #[DataProvider('presenceConditions')]
    public function testPresence(string $condition, ?bool $exists, ?bool $null): void
    {
        $item = $this->parseOne($condition);

        $this->assertSame($exists, $item->exists);
        $this->assertSame($null, $item->null);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidConditions(): array
    {
        return [
            'unknown operator'   => ['status ~ 5'],
            'unquoted string'    => ['method = GET'],
            'string comparison'  => ['method > "GET"'],
            'boolean comparison' => ['cache.hit > true'],
            'no value'           => ['status >='],
            'no key'             => ['>= 500'],
            'dash in the key'    => ['user-agent exists'],
            'quote in the key'   => ["a'b exists"],
            'empty key segment'  => ['a..b exists'],
            'line break in key'  => ["a\nb exists"],
            'infinite number'    => ['size > 1' . str_repeat('0', 400) . '.5'],
        ];
    }

    #[DataProvider('invalidConditions')]
    public function testInvalid(string $condition): void
    {
        try {
            new McpTraceDataFilterParser()->parse([$condition]);

            $this->fail('No exception thrown');
        } catch (McpTraceDataFilterInvalidException $exception) {
            $this->assertSame($condition, $exception->condition);
        }
    }

    private function parseOne(string $condition): TraceDataFilterItemParameters
    {
        $items = new McpTraceDataFilterParser()->parse([$condition]);

        $this->assertCount(1, $items);

        return $items[0];
    }
}
