<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Enums\NginxErrorLogLevelEnum;

readonly class NginxErrorLogFormat extends AbstractLineLogFormat
{
    private const string LINE_PATTERN = '/^(?:(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}) \[([a-z]+)\] )?[^\n]*$/m';

    protected function getLinePattern(): string
    {
        return self::LINE_PATTERN;
    }

    protected function parseTime(string $text): ?int
    {
        return $this->timeParser->toUnixTime(
            year: (int) substr($text, 0, 4),
            month: (int) substr($text, 5, 2),
            day: (int) substr($text, 8, 2),
            hour: (int) substr($text, 11, 2),
            minute: (int) substr($text, 14, 2),
            second: (int) substr($text, 17, 2),
            zone: null
        );
    }

    protected function parseLevel(string $text): int
    {
        return match ($text) {
            'debug'  => NginxErrorLogLevelEnum::Debug->value,
            'info'   => NginxErrorLogLevelEnum::Info->value,
            'notice' => NginxErrorLogLevelEnum::Notice->value,
            'warn'   => NginxErrorLogLevelEnum::Warn->value,
            'error'  => NginxErrorLogLevelEnum::Error->value,
            'crit'   => NginxErrorLogLevelEnum::Crit->value,
            'alert'  => NginxErrorLogLevelEnum::Alert->value,
            'emerg'  => NginxErrorLogLevelEnum::Emerg->value,
            default  => 0,
        };
    }
}
