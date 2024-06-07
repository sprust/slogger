<?php

namespace App\Modules\Trace\Enums;

enum TraceMetricIndicatorEnum: string
{
    case Count = 'count';
    case Duration = 'duration';
    case Memory = 'memory';
    case Cpu = 'cpu';
}
