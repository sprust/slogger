<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Enums;

enum TraceGroupFieldEnum: string
{
    case Service  = 'service';

    case Type     = 'type';

    case Status   = 'status';

    case Hour     = 'hour';

    case Minute10 = 'minute10';
}
