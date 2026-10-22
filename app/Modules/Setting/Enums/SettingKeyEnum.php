<?php

declare(strict_types=1);

namespace App\Modules\Setting\Enums;

enum SettingKeyEnum: string
{
    case TracesLifetimeHours = 'traces_lifetime_hours';
    case LogsNginxKeepDays = 'logs_nginx_keep_days';
}
