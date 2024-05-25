<?php

namespace App\Modules\TraceAggregator\Enums;

enum TraceDataFilterCompNumericTypeEnum: string
{
    case Eq = '=';
    case Neq = '!=';
    case Gt = '>';
    case Gte = '>=';
    case Lt = '<';
    case Lte = '<=';
}
