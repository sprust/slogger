<?php

namespace App\Modules\TraceAggregator\Enums;

enum TimestampPeriodEnum: string
{
    case Minute5 = 'minute5';
    case Minute30 = 'minute30';
    case Hour = 'hour';
    case Hour4 = 'hour4';
    case Hour12 = 'hour12';
    case Day = 'day';
    case Day3 = 'day3';
    case Day7 = 'day7';
    case Day15 = 'day15';
    case Month = 'month';
    case Month3 = 'month3';
    case Month6 = 'month6';
    case Year = 'year';
}
