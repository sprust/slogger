<?php

namespace App\Modules\ProjectLogs;

enum ProjectLogTypeEnum: string
{
    case Log = 'log';
    case Request = 'request';
}
