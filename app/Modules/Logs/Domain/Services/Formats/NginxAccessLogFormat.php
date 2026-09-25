<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Entities\Entry\LogEntryDetailsObject;
use App\Modules\Logs\Entities\Entry\LogEntryFieldObject;
use App\Modules\Logs\Entities\Formats\LogLevelNameObject;
use App\Modules\Logs\Enums\HttpStatusClassEnum;

readonly class NginxAccessLogFormat extends AbstractLineLogFormat
{
    private const string LINE_PATTERN = '/^(?:\S+ \S+ \S+ \[(\d{2}\/[A-Za-z]{3}\/\d{4}:\d{2}:\d{2}:\d{2} [+-]\d{4})\] "(?:[^"\\\\]|\\\\.)*" (\d{3}) )?[^\n]*$/m';

    private const string ENTRY_PATTERN = '/^(\S+) \S+ (\S+) \[[^\]]+\] "((?:[^"\\\\]|\\\\.)*)" (\d{3}) (\S+)(?: "((?:[^"\\\\]|\\\\.)*)" "((?:[^"\\\\]|\\\\.)*)")?/';

    public function parseEntry(string $text): LogEntryDetailsObject
    {
        $line = rtrim($text, "\r\n");

        if (preg_match(self::ENTRY_PATTERN, $line, $match) !== 1) {
            return new LogEntryDetailsObject(message: $line, context: null, fields: []);
        }

        $request = explode(' ', $match[3], 3);

        return new LogEntryDetailsObject(
            message: $match[3],
            context: null,
            fields: [
                new LogEntryFieldObject(key: 'ip', value: $match[1]),
                new LogEntryFieldObject(key: 'user', value: $this->nullIfDash($match[2])),
                new LogEntryFieldObject(key: 'method', value: count($request) === 3 ? $request[0] : null),
                new LogEntryFieldObject(key: 'path', value: count($request) === 3 ? $request[1] : null),
                new LogEntryFieldObject(key: 'protocol', value: count($request) === 3 ? $request[2] : null),
                new LogEntryFieldObject(key: 'status', value: $match[4]),
                new LogEntryFieldObject(key: 'bytes', value: $this->nullIfDash($match[5])),
                new LogEntryFieldObject(key: 'referer', value: $this->nullIfDash($match[6] ?? '-')),
                new LogEntryFieldObject(key: 'user_agent', value: $this->nullIfDash($match[7] ?? '-')),
            ]
        );
    }

    public function getLevelNames(): array
    {
        $names = [];

        foreach (HttpStatusClassEnum::cases() as $case) {
            $names[] = new LogLevelNameObject(level: $case->value, name: sprintf('%dxx', $case->value));
        }

        return $names;
    }

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

    private function nullIfDash(string $value): ?string
    {
        return $value === '-' || $value === '' ? null : $value;
    }
}
