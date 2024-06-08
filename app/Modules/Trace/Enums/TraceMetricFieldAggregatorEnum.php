<?php

namespace App\Modules\Trace\Enums;

enum TraceMetricFieldAggregatorEnum: string
{
    case Sum = 'sum';
    case Avg = 'avg';
    case Min = 'min';
    case Max = 'max';
}
