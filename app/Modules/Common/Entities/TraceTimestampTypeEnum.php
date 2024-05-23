<?php

namespace App\Modules\Common\Entities;

enum TraceTimestampTypeEnum: string
{
    case Year = 'Year';
    case Month = 'Month';
    case Day = 'Day';
    case Hour = 'Hour';
    case Minute = 'Minute';
    case Sec30 = 'sec30';
    case Sec10 = 'sec10';
    case Sec5 = 'sec5';
}
