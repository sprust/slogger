<?php

namespace App\Modules\TracesAggregator\Enums;

enum TraceDataFilterCompStringTypeEnum: string
{
    case Eq = 'equals';
    case Con = 'contains';
    case Starts = 'starts';
    case Ends = 'ends';
}
