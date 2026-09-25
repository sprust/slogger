<?php

declare(strict_types=1);

namespace App\Modules\Logs\Enums;

enum ReceiverLogLevelEnum: int
{
    case Debug = 1;
    case Info = 2;
    case Warn = 3;
    case Error = 4;
}
