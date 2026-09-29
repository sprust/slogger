<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterBooleanParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterItemParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterNumericParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterStringParameters;
use App\Modules\Trace\Repositories\Services\ClickhouseDataPathTypes;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceFilterBuilder;
use App\Modules\Trace\Repositories\Services\TraceDataPathResolver;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ClickhouseTraceFilterBuilderTest extends TestCase
{
    public function testNoFiltersIsEverything(): void
    {
        $condition = $this->builder()->build();

        self::assertSame('1', $condition->sql);
        self::assertSame([], $condition->params);
    }

    public function testBaseFiltersGoIntoParameters(): void
    {
        $condition = $this->builder()->build(
            serviceIds: [1, 2],
            traceIds: ["it's"],
            loggedAtFrom: Carbon::parse('2026-09-29 10:00:00', 'UTC'),
            loggedAtTo: Carbon::parse('2026-09-29 11:00:00', 'UTC'),
            types: ['request'],
            tags: ['api', 'v2'],
            statuses: ['failed'],
            durationFrom: 0.5,
            durationTo: 2.0,
            memoryFrom: 10.0,
            cpuTo: 90.0,
        );

        self::assertSame(
            'sid IN {f0:Array(UInt32)} AND tid IN {f1:Array(String)} '
            . "AND lat >= {f2:DateTime64(6, 'UTC')} AND lat <= {f3:DateTime64(6, 'UTC')} "
            . 'AND tp IN {f4:Array(String)} AND hasAll(tgs, {f5:Array(String)}) AND st IN {f6:Array(String)} '
            . 'AND dur >= {f7:Float64} AND dur <= {f8:Float64} AND mem >= {f9:Float64} AND cpu <= {f10:Float64}',
            $condition->sql
        );

        self::assertSame([1, 2], $condition->params['f0']);
        self::assertSame(["it's"], $condition->params['f1']);
        self::assertSame(['api', 'v2'], $condition->params['f5']);
        self::assertSame(0.5, $condition->params['f7']);
    }

    public function testNoTraceHasProfiling(): void
    {
        self::assertSame('0', $this->builder()->build(hasProfiling: true)->sql);
        self::assertSame('1', $this->builder()->build(hasProfiling: false)->sql);
    }

    public function testNumberIsComparedAsANumber(): void
    {
        $condition = $this->builder()->build(
            data: $this->data(numeric: new TraceDataFilterNumericParameters(500, TraceDataFilterCompNumericTypeEnum::Gte))
        );

        self::assertSame(
            "coalesce(if(dynamicType(dt.`response`.`status`) IN ('Int64', 'UInt64', 'Float64'), "
            . "accurateCastOrNull(dt.`response`.`status`, 'Float64'), NULL) >= {f0:Float64}, 0)",
            $condition->sql
        );
        self::assertSame(500.0, $condition->params['f0']);
    }

    public function testNumberNotEqualAlsoMatchesTracesWithoutIt(): void
    {
        $condition = $this->builder()->build(
            data: $this->data(numeric: new TraceDataFilterNumericParameters(5, TraceDataFilterCompNumericTypeEnum::Neq))
        );

        self::assertStringStartsWith('NOT coalesce(', $condition->sql);
        self::assertStringContainsString(' = {f0:Float64}', $condition->sql);
    }

    /**
     * @return array<string, array{TraceDataFilterCompStringTypeEnum, string}>
     */
    public static function stringProvider(): array
    {
        return [
            'equals'      => [TraceDataFilterCompStringTypeEnum::Eq, "coalesce(dynamicElement(dt.`response`.`status`, 'String') = {f0:String}, 0)"],
            'contains'    => [TraceDataFilterCompStringTypeEnum::Con, "coalesce(position(dynamicElement(dt.`response`.`status`, 'String'), {f0:String}) > 0, 0)"],
            'starts with' => [TraceDataFilterCompStringTypeEnum::Starts, "coalesce(startsWith(dynamicElement(dt.`response`.`status`, 'String'), {f0:String}), 0)"],
            'ends with'   => [TraceDataFilterCompStringTypeEnum::Ends, "coalesce(endsWith(dynamicElement(dt.`response`.`status`, 'String'), {f0:String}), 0)"],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('stringProvider')]
    public function testStringIsComparedAsText(TraceDataFilterCompStringTypeEnum $comp, string $sql): void
    {
        $condition = $this->builder()->build(
            data: $this->data(string: new TraceDataFilterStringParameters('a.b', $comp))
        );

        self::assertSame($sql, $condition->sql);
        self::assertSame('a.b', $condition->params['f0']);
    }

    public function testStringNotEqualNeedsTheKey(): void
    {
        $condition = $this->builder()->build(
            data: $this->data(string: new TraceDataFilterStringParameters('ok', TraceDataFilterCompStringTypeEnum::Neq))
        );

        self::assertSame(
            "(JSONHas(dt_raw, 'response', 'status') AND NOT "
            . "coalesce(dynamicElement(dt.`response`.`status`, 'String') = {f0:String}, 0))",
            $condition->sql
        );
    }

    public function testBoolean(): void
    {
        $condition = $this->builder()->build(
            data: $this->data(boolean: new TraceDataFilterBooleanParameters(true))
        );

        self::assertSame("coalesce(dynamicElement(dt.`response`.`status`, 'Bool') = {f0:Bool}, 0)", $condition->sql);
        self::assertTrue($condition->params['f0']);
    }

    public function testPresenceAndNullAreReadFromTheRawData(): void
    {
        self::assertSame(
            "JSONHas(dt_raw, 'response', 'status')",
            $this->builder()->build(data: $this->data(exists: true))->sql
        );
        self::assertSame(
            "NOT JSONHas(dt_raw, 'response', 'status')",
            $this->builder()->build(data: $this->data(exists: false))->sql
        );
        self::assertSame(
            "(JSONHas(dt_raw, 'response', 'status') AND JSONType(dt_raw, 'response', 'status') = 'Null')",
            $this->builder()->build(data: $this->data(null: true))->sql
        );
        self::assertSame(
            "(JSONHas(dt_raw, 'response', 'status') AND JSONType(dt_raw, 'response', 'status') != 'Null')",
            $this->builder()->build(data: $this->data(null: false))->sql
        );
    }

    public function testPathThroughAnArrayOfObjectsMatchesAnyElement(): void
    {
        $condition = $this->builder(arrayPaths: ['items'])->build(
            data: new TraceDataFilterParameters(
                filter: [
                    new TraceDataFilterItemParameters(
                        field: 'dt.items.price',
                        null: null,
                        exists: null,
                        numeric: new TraceDataFilterNumericParameters(10, TraceDataFilterCompNumericTypeEnum::Eq),
                        string: null,
                        boolean: null
                    ),
                ]
            )
        );

        self::assertStringContainsString(' OR arrayExists(__v -> coalesce(', $condition->sql);
        self::assertStringContainsString('dt.`items`[].`price`', $condition->sql);
        self::assertStringContainsString('dt.`items`.`price`', $condition->sql);
    }

    public function testInvalidPathIsRefusedBeforeAnySql(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->builder()->build(
            data: new TraceDataFilterParameters(
                filter: [
                    new TraceDataFilterItemParameters(
                        field: "dt.a'); DROP",
                        null: null,
                        exists: true,
                        numeric: null,
                        string: null,
                        boolean: null
                    ),
                ]
            )
        );
    }

    public function testEveryDataValueIsAParameter(): void
    {
        $condition = $this->builder()->build(
            data: $this->data(string: new TraceDataFilterStringParameters("'; DROP TABLE traces --", TraceDataFilterCompStringTypeEnum::Eq))
        );

        self::assertStringNotContainsString('DROP', $condition->sql);
    }

    private function data(
        ?bool $null = null,
        ?bool $exists = null,
        ?TraceDataFilterNumericParameters $numeric = null,
        ?TraceDataFilterStringParameters $string = null,
        ?TraceDataFilterBooleanParameters $boolean = null,
    ): TraceDataFilterParameters {
        return new TraceDataFilterParameters(
            filter: [
                new TraceDataFilterItemParameters(
                    field: 'dt.response.status',
                    null: $null,
                    exists: $exists,
                    numeric: $numeric,
                    string: $string,
                    boolean: $boolean
                ),
            ]
        );
    }

    /**
     * @param string[] $arrayPaths
     */
    private function builder(array $arrayPaths = []): ClickhouseTraceFilterBuilder
    {
        $pathTypes = $this->createMock(ClickhouseDataPathTypes::class);
        $pathTypes->method('arrayPaths')->willReturn($arrayPaths);

        return new ClickhouseTraceFilterBuilder(
            new TraceDataPathResolver($pathTypes)
        );
    }
}
