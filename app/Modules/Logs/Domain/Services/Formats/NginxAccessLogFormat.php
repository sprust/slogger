<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Enums\HttpStatusClassEnum;

readonly class NginxAccessLogFormat extends AbstractLineLogFormat
{
    private const string LINE_PATTERN = '/^(?:\S+ \S+ \S+ \[(\d{2}\/[A-Za-z]{3}\/\d{4}:\d{2}:\d{2}:\d{2} [+-]\d{4})\] "(?:[^"\\\\]|\\\\.)*" (\d{3}) )?[^\n]*$/m';

    protected function getLinePattern(): string
    {
        return self::LINE_PATTERN;
    }

    protected function parseTime(string $text): ?int
    {
        $month = $this->timeParser->parseMonth(substr($text, 3, 3));

        if ($month === null) {
            return null;
        }

        return $this->timeParser->toUnixTime(
            year: (int) substr($text, 7, 4),
            month: $month,
            day: (int) substr($text, 0, 2),
            hour: (int) substr($text, 12, 2),
            minute: (int) substr($text, 15, 2),
            second: (int) substr($text, 18, 2),
            zone: substr($text, 21, 5)
        );
    }

    protected function parseLevel(string $text): int
    {
        return match ((int) ($text[0] ?? 0)) {
            1       => HttpStatusClassEnum::Informational->value,
            2       => HttpStatusClassEnum::Success->value,
            3       => HttpStatusClassEnum::Redirection->value,
            4       => HttpStatusClassEnum::ClientError->value,
            5       => HttpStatusClassEnum::ServerError->value,
            default => 0,
        };
    }
}
