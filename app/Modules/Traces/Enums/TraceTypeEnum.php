<?php

namespace App\Modules\Traces\Enums;

enum TraceTypeEnum: string
{
    case Log = 'log';
    case Request = 'request';
    case Database = 'database';
}
