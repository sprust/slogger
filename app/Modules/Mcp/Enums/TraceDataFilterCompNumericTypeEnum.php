<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Enums;

enum TraceDataFilterCompNumericTypeEnum: string
{
    case Eq  = '=';

    case Neq = '!=';

    case Gt  = '>';

    case Gte = '>=';

    case Lt  = '<';

    case Lte = '<=';
}
