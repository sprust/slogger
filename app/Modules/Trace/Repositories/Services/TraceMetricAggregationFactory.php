<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Enums\TraceMetricFieldAggregatorEnum;

class TraceMetricAggregationFactory
{
    /**
     * @return array<string, mixed>
     */
    public function makeExpression(TraceMetricFieldAggregatorEnum $aggregation, string $fieldName): array
    {
        return match ($aggregation) {
            TraceMetricFieldAggregatorEnum::Sum => ['$sum' => 1],
            TraceMetricFieldAggregatorEnum::Avg => ['$avg' => "\$$fieldName"],
            TraceMetricFieldAggregatorEnum::Min => ['$min' => "\$$fieldName"],
            TraceMetricFieldAggregatorEnum::Max => ['$max' => "\$$fieldName"],
            TraceMetricFieldAggregatorEnum::P50 => $this->makePercentile($fieldName, 0.5),
            TraceMetricFieldAggregatorEnum::P95 => $this->makePercentile($fieldName, 0.95),
            TraceMetricFieldAggregatorEnum::P99 => $this->makePercentile($fieldName, 0.99),
        };
    }

    public function readValue(mixed $value): float
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * @return array<string, mixed>
     */
    private function makePercentile(string $fieldName, float $percent): array
    {
        return [
            '$percentile' => [
                'input'  => "\$$fieldName",
                'p'      => [$percent],
                'method' => 'approximate',
            ],
        ];
    }
}
