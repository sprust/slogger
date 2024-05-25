<?php

namespace App\Modules\TraceAggregator\Enums;

enum TraceTimestampEnum: string
{
    case S5 = 's5';
    case S10 = 's10';
    case S30 = 's30';
    case Min = 'min';
    case Min5 = 'min5';
    case Min10 = 'min10';
    case Min30 = 'min30';
    case H = 'h';
    case H4 = 'h4';
    case H12 = 'h12';
    case D = 'd';
    case M = 'm';
}
