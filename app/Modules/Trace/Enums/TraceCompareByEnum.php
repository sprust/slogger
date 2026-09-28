<?php

declare(strict_types=1);

namespace App\Modules\Trace\Enums;

enum TraceCompareByEnum: string
{
    case Type = 'type';
    case Service = 'service';
    case Tag = 'tag';
    case Data = 'data';
}
