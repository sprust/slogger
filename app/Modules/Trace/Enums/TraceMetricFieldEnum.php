<?php

declare(strict_types=1);

namespace App\Modules\Trace\Enums;

enum TraceMetricFieldEnum: string
{
    case Count = 'count';
    case Duration = 'duration';
    case Memory = 'memory';
    case Cpu = 'cpu';
}
