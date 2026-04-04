<?php

declare(strict_types=1);

namespace App\Modules\Trace\Enums;

enum TraceTreeCacheStateStatusEnum: string
{
    case InProcess = 'inProcess';
    case Finished = 'finished';
    case Failed = 'failed';
    case Canceled = 'canceled';
}
