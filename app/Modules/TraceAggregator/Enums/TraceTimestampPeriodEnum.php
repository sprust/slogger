<?php

namespace App\Modules\TraceAggregator\Enums;

enum TraceTimestampPeriodEnum: string
{
    case Minute5 = '5 minutes';
    case Minute30 = '30 minutes';
    case Hour = '1 hour';
    case Hour4 = '4 hours';
    case Hour12 = '12 hours';
    case Day = '1 day';
    case Day3 = '3 days';
    case Day7 = '7 days';
    case Day15 = '15 days';
    case Month = '1 month';
    case Month3 = '3 months';
    case Month6 = '6 month';
    case Year = '1 year';
}
