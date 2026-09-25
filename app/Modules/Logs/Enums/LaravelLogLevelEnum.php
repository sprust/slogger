<?php

declare(strict_types=1);

namespace App\Modules\Logs\Enums;

enum LaravelLogLevelEnum: int
{
    case Debug = 1;
    case Info = 2;
    case Notice = 3;
    case Warning = 4;
    case Error = 5;
    case Critical = 6;
    case Alert = 7;
    case Emergency = 8;
}
