<?php

namespace App\Modules\Trace\Enums;

enum PeriodicTraceStepEnum: string
{
    case OneHour = 'one_hour';
    case TwoHours = 'two_hours';
    case FourHours = 'four_hours';
    case SixHours = 'six_hours';
}
