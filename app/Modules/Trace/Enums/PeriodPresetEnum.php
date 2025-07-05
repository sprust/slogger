<?php

declare(strict_types=1);

namespace App\Modules\Trace\Enums;

enum PeriodPresetEnum: string
{
    case Custom = 'custom';
    case LastHour = 'last_hour';
    case Last2Hour = 'last_2_hours';
    case Last3Hour = 'last_3_hours';
    case Last6Hour = 'last_6_hours';
    case Last12Hour = 'last_12_hours';
    case LastDay = 'last_day';
    case Last3Day = 'last_3_day';
    case LastWeek = 'last_week';
    case Last2Week = 'last_2_week';
    case LastMonth = 'last_month';
}
