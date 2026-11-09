<?php

declare(strict_types=1);

namespace App\Modules\Logs\Enums;

enum SconcurJournalLogLevelEnum: int
{
    case Info = 1;
    case Warn = 2;
    case Error = 3;
}
