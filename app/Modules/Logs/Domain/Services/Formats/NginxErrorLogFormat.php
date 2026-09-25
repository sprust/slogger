<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Entities\Entry\LogEntryDetailsObject;
use App\Modules\Logs\Entities\Entry\LogEntryFieldObject;
use App\Modules\Logs\Entities\Formats\LogLevelNameObject;
use App\Modules\Logs\Enums\NginxErrorLogLevelEnum;

readonly class NginxErrorLogFormat extends AbstractLineLogFormat
{
    private const string LINE_PATTERN = '/^(?:(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}) \[([a-z]+)\] )?[^\n]*$/m';

    private const string ENTRY_PATTERN  = '/^\S+ \S+ \[[a-z]+\] (\d+)#(\d+): (?:\*(\d+) )?(.*)$/s';
    private const string DETAIL_PATTERN = '/, (client|server|request|upstream|host|referrer): ("[^"]*"|[^,]*)/';
    private const array DETAIL_KEYS     = ['client', 'server', 'request', 'upstream', 'host', 'referrer'];

    public function parseEntry(string $text): LogEntryDetailsObject
    {
        $line = rtrim($text, "\r\n");

        if (preg_match(self::ENTRY_PATTERN, $line, $match) !== 1) {
            return new LogEntryDetailsObject(message: $line, context: null, fields: []);
        }

        $rest = $match[4];

        $message = $rest;
        $values  = [];

        if (preg_match_all(self::DETAIL_PATTERN, $rest, $details, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) > 0) {
            $message = substr($rest, 0, $details[0][0][1]);

            foreach ($details as $detail) {
                $values[$detail[1][0]] = trim($detail[2][0], '"');
            }
        }

        $fields = [
            new LogEntryFieldObject(key: 'pid', value: $match[1]),
            new LogEntryFieldObject(key: 'tid', value: $match[2]),
            new LogEntryFieldObject(key: 'connection', value: $match[3] === '' ? null : $match[3]),
        ];

        foreach (self::DETAIL_KEYS as $key) {
            $fields[] = new LogEntryFieldObject(key: $key, value: $values[$key] ?? null);
        }

        return new LogEntryDetailsObject(message: $message, context: null, fields: $fields);
    }

    public function getLevelNames(): array
    {
        $names = [];

        foreach (NginxErrorLogLevelEnum::cases() as $case) {
            $names[] = new LogLevelNameObject(level: $case->value, name: strtolower($case->name));
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
