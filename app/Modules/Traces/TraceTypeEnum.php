<?php

namespace App\Modules\Traces;

enum TraceTypeEnum: string
{
    case Log = 'log';
    case Request = 'request';
    case Database = 'database';
}
