<?php

declare(strict_types=1);

namespace App\Modules\Logs\Enums;

enum LogTypeEnum: string
{
    case Laravel = 'laravel';
    case NginxAccess = 'nginx_access';
    case NginxError = 'nginx_error';
}
