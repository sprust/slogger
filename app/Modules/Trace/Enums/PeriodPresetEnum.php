<?php

declare(strict_types=1);

namespace App\Modules\Trace\Enums;

enum PeriodPresetEnum: string
{
    case LastHour = 'last_hour';
    case LastDay = 'last_day';
    case LastWeek = 'last_week';
    case LastMonth = 'last_month';
}
