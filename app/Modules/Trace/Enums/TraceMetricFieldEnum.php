<?php

namespace App\Modules\Trace\Enums;

enum TraceMetricFieldEnum: string
{
    case Count = 'count';
    case Duration = 'duration';
    case Memory = 'memory';
    case Cpu = 'cpu';
}
