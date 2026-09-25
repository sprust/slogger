<?php

declare(strict_types=1);

namespace App\Modules\Logs\Enums;

enum LogCursorDirectionEnum: string
{
    case Older = 'older';
    case Newer = 'newer';
}
