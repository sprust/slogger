<?php

declare(strict_types=1);

namespace App\Modules\Trace\Enums;

enum TraceMetricFieldAggregatorEnum: string
{
    case Sum = 'sum';
    case Avg = 'avg';
    case Min = 'min';
    case Max = 'max';
    case P50 = 'p50';
    case P95 = 'p95';
    case P99 = 'p99';
}
