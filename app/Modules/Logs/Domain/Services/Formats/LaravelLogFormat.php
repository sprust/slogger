<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Entities\Entry\LogEntryDetailsObject;
use App\Modules\Logs\Entities\Entry\LogEntryFieldObject;
use App\Modules\Logs\Entities\Formats\LogLevelNameObject;
use App\Modules\Logs\Entities\Index\LogEntryStartObject;
use App\Modules\Logs\Enums\LaravelLogLevelEnum;

readonly class LaravelLogFormat implements LogFormatInterface
{
    private const string ENTRY_HEADER_PATTERN = '/^\[[^\]]+\] (\S+?)\.[A-Z]+: /';
    private const int MAX_CONTEXT_CANDIDATES  = 20;

    private const string HEADER_PATTERN = '/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})?)\] \S+?\.([A-Z]+): /m';

    public function __construct(
        private LogTimeParser $timeParser
    ) {
    }

    public function findEntryStarts(string $chunk): array
    {
        preg_match_all(self::HEADER_PATTERN, $chunk, $matches, PREG_OFFSET_CAPTURE);

        $starts = [];

        $lastText = null;
        $lastTime = null;

        foreach ($matches[0] as $index => $match) {
            $timeText = $matches[1][$index][0];

            if ($timeText !== $lastText) {
                $lastText = $timeText;
                $lastTime = $this->parseTime($timeText);
            }

            $starts[] = new LogEntryStartObject(
                offset: $match[1],
                loggedAt: $lastTime,
                level: $this->parseLevel($matches[2][$index][0])
            );
        }

        return $starts;
    }

    public function parseEntry(string $text): LogEntryDetailsObject
    {
        if (preg_match(self::ENTRY_HEADER_PATTERN, $text, $match) !== 1) {
            return new LogEntryDetailsObject(
                message: rtrim($text),
                context: null,
                fields: []
            );
        }

        $body = rtrim(substr($text, strlen($match[0])));

        for ($index = 0; $index < 2 && str_ends_with($body, ' []'); ++$index) {
            $body = substr($body, 0, -3);
        }

        $contextPosition = $this->findContextPosition($body);

        return new LogEntryDetailsObject(
            message: $contextPosition === null ? $body : rtrim(substr($body, 0, $contextPosition)),
            context: $contextPosition === null ? null : $this->normalizeJson(substr($body, $contextPosition)),
            fields: [
                new LogEntryFieldObject(key: 'env', value: $match[1]),
            ]
        );
    }

    public function getLevelNames(): array
    {
        $names = [];

        foreach (LaravelLogLevelEnum::cases() as $case) {
            $names[] = new LogLevelNameObject(level: $case->value, name: strtoupper($case->name));
        }

        return $names;
    }

    private function findContextPosition(string $body): ?int
    {
        if (!str_ends_with($body, '}') && !str_ends_with($body, ']')) {
            return null;
        }

        preg_match_all('/ (?=[{\[])/', $body, $candidates, PREG_OFFSET_CAPTURE);

        foreach (array_slice($candidates[0], 0, self::MAX_CONTEXT_CANDIDATES) as $candidate) {
            $position = $candidate[1] + 1;

            if ($this->normalizeJson(substr($body, $position)) !== null) {
                return $position;
            }
        }

        return null;
    }

    private function normalizeJson(string $json): ?string
    {
        $decoded = json_decode(str_replace(["\r", "\n"], ['\r', '\n'], $json), true);

        if (!is_array($decoded)) {
            return null;
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: null;
    }

    private function parseTime(string $text): ?int
    {
        $zone = substr(ltrim(substr($text, 19), '.0123456789'), 0, 6);

        return $this->timeParser->toUnixTime(
            year: (int) substr($text, 0, 4),
            month: (int) substr($text, 5, 2),
            day: (int) substr($text, 8, 2),
            hour: (int) substr($text, 11, 2),
            minute: (int) substr($text, 14, 2),
            second: (int) substr($text, 17, 2),
            zone: $zone === '' ? null : $zone
        );
    }

    private function parseLevel(string $level): int
    {
        return match ($level) {
            'DEBUG'     => LaravelLogLevelEnum::Debug->value,
            'INFO'      => LaravelLogLevelEnum::Info->value,
            'NOTICE'    => LaravelLogLevelEnum::Notice->value,
            'WARNING'   => LaravelLogLevelEnum::Warning->value,
            'ERROR'     => LaravelLogLevelEnum::Error->value,
            'CRITICAL'  => LaravelLogLevelEnum::Critical->value,
            'ALERT'     => LaravelLogLevelEnum::Alert->value,
            'EMERGENCY' => LaravelLogLevelEnum::Emergency->value,
            default     => 0,
        };
    }
}
