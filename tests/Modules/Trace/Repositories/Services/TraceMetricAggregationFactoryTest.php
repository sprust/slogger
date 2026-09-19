<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Enums\TraceMetricFieldAggregatorEnum;
use App\Modules\Trace\Repositories\Services\TraceMetricAggregationFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TraceMetricAggregationFactoryTest extends TestCase
{
    public function testACountIsSummedByOne(): void
    {
        self::assertSame(
            expected: ['$sum' => 1],
            actual: new TraceMetricAggregationFactory()->makeExpression(
                aggregation: TraceMetricFieldAggregatorEnum::Sum,
                fieldName: 'count'
            )
        );
    }

    #[DataProvider('plainAggregationProvider')]
    public function testAPlainAggregationTakesTheField(
        TraceMetricFieldAggregatorEnum $aggregation,
        string $operator
    ): void {
        self::assertSame(
            expected: [$operator => '$dur'],
            actual: new TraceMetricAggregationFactory()->makeExpression(
                aggregation: $aggregation,
                fieldName: 'dur'
            )
        );
    }

    /**
     * @return array<string, array{TraceMetricFieldAggregatorEnum, string}>
     */
    public static function plainAggregationProvider(): array
    {
        return [
            'avg' => [TraceMetricFieldAggregatorEnum::Avg, '$avg'],
            'min' => [TraceMetricFieldAggregatorEnum::Min, '$min'],
            'max' => [TraceMetricFieldAggregatorEnum::Max, '$max'],
        ];
    }

    #[DataProvider('percentileProvider')]
    public function testAPercentileAsksForItsShare(
        TraceMetricFieldAggregatorEnum $aggregation,
        float $percent
    ): void {
        self::assertSame(
            expected: [
                '$percentile' => [
                    'input'  => '$dt.payload.size',
                    'p'      => [$percent],
                    'method' => 'approximate',
                ],
            ],
            actual: new TraceMetricAggregationFactory()->makeExpression(
                aggregation: $aggregation,
                fieldName: 'dt.payload.size'
            )
        );
    }

    /**
     * @return array<string, array{TraceMetricFieldAggregatorEnum, float}>
     */
    public static function percentileProvider(): array
    {
        return [
            'p50' => [TraceMetricFieldAggregatorEnum::P50, 0.5],
            'p95' => [TraceMetricFieldAggregatorEnum::P95, 0.95],
            'p99' => [TraceMetricFieldAggregatorEnum::P99, 0.99],
        ];
    }

    public function testAPercentileValueIsTakenOutOfItsList(): void
    {
        self::assertSame(
            expected: 12.5,
            actual: new TraceMetricAggregationFactory()->readValue([12.5])
        );
    }

    #[DataProvider('emptyValueProvider')]
    public function testAValueThatIsNotANumberIsZero(mixed $value): void
    {
        self::assertSame(
            expected: 0.0,
            actual: new TraceMetricAggregationFactory()->readValue($value)
        );
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function emptyValueProvider(): array
    {
        return [
            'null'           => [null],
            'empty list'     => [[]],
            'null in a list' => [[null]],
            'a string'       => ['text'],
        ];
    }

    public function testAPlainValueIsRead(): void
    {
        self::assertSame(
            expected: 7.0,
            actual: new TraceMetricAggregationFactory()->readValue(7)
        );
    }
}
