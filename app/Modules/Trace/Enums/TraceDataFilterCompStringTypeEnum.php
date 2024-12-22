<?php

declare(strict_types=1);

namespace App\Modules\Trace\Enums;

enum TraceDataFilterCompStringTypeEnum: string
{
    case Eq = 'equals';
    case Con = 'contains';
    case Starts = 'starts';
    case Ends = 'ends';
}
