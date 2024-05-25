<?php

namespace App\Modules\Common\Enums;

enum TraceTimestampMetricEnum: string
{
    case M = 'm';
    case D = 'd';
    case H12 = 'h12';
    case H4 = 'h4';
    case H = 'h';
    case Min30 = 'min30';
    case Min10 = 'min10';
    case Min5 = 'min5';
    case Min = 'min';
    case S30 = 's30';
    case S10 = 's10';
    case S5 = 's5';
}
