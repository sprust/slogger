<?php

declare(strict_types=1);

namespace App\Modules\Trace\Enums;

enum PeriodPresetEnum: string
{
    case Custom = 'custom';
    case LastHour = 'last_hour';
    case Last2Hours = 'last_2_hours';
    case Last3Hours = 'last_3_hours';
    case Last6Hours = 'last_6_hours';
    case Last12Hours = 'last_12_hours';
    case LastDay = 'last_day';
    case Last3Days = 'last_3_days';
    case LastWeek = 'last_week';
    case Last2Weeks = 'last_2_weeks';
    case LastMonth = 'last_month';
}
