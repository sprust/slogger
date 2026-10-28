<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Entities\Entry\LogEntryDetailsObject;
use App\Modules\Logs\Entities\Entry\LogEntryFieldObject;
use App\Modules\Logs\Entities\Formats\LogLevelNameObject;
use App\Modules\Logs\Enums\HttpStatusClassEnum;

/**
 * Access lines of the sconcur http workers, in UTC: `Y-m-dTH:i:s.u METHOD path status 1.23ms group#slot pid=N`.
 */
readonly class SconcurAccessLogFormat extends AbstractLineLogFormat
{
    private const string LINE_PATTERN = '/^(?:(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})\.\d+ \S+ \S+ (\d{3}) )?[^\n]*$/m';

    private const string ENTRY_PATTERN = '/^\S+ (\S+) (\S+) (\d{3}) ([\d.]+)ms(?: (\S*#\d+) pid=(\d+))?/';

    public function parseEntry(string $text): LogEntryDetailsObject
    {
        $line = rtrim($text, "\r\n");

        if (preg_match(self::ENTRY_PATTERN, $line, $match) !== 1) {
            return new LogEntryDetailsObject(message: $line, context: null, fields: []);
        }

        return new LogEntryDetailsObject(
            message: sprintf('%s %s', $match[1], $match[2]),
            context: null,
            fields: [
                new LogEntryFieldObject(key: 'method', value: $match[1]),
                new LogEntryFieldObject(key: 'path', value: $match[2]),
                new LogEntryFieldObject(key: 'status', value: $match[3]),
                new LogEntryFieldObject(key: 'duration_ms', value: $match[4]),
                new LogEntryFieldObject(key: 'worker', value: ($match[5] ?? '') === '' ? null : $match[5]),
                new LogEntryFieldObject(key: 'pid', value: ($match[6] ?? '') === '' ? null : $match[6]),
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
