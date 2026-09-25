<?php

declare(strict_types=1);

namespace App\Modules\Logs\Enums;

enum NginxErrorLogLevelEnum: int
{
    case Debug = 1;
    case Info = 2;
    case Notice = 3;
    case Warn = 4;
    case Error = 5;
    case Crit = 6;
    case Alert = 7;
    case Emerg = 8;
}
