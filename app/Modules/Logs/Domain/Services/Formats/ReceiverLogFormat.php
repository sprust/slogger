<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Entities\Entry\LogEntryDetailsObject;
use App\Modules\Logs\Entities\Formats\LogLevelNameObject;
use App\Modules\Logs\Entities\Index\LogEntryStartObject;
use App\Modules\Logs\Enums\ReceiverLogLevelEnum;

/**
 * The receiver's slog: `Y-m-d H:i:s.v LEVEL message` in UTC (servers/receiver formatter.go).
 * A stack trace follows on its own lines, so an entry runs until the next header.
 */
readonly class ReceiverLogFormat implements LogFormatInterface
{
    private const string HEADER_PATTERN = '/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\.\d{3} ([A-Z]+) /m';

    private const string ENTRY_HEADER_PATTERN = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{3} [A-Z]+ /';

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
        $body = preg_match(self::ENTRY_HEADER_PATTERN, $text, $match) === 1
            ? substr($text, strlen($match[0]))
            : $text;

        $lineEnd = strpos($body, "\n");

        return new LogEntryDetailsObject(
            message: rtrim($lineEnd === false ? $body : substr($body, 0, $lineEnd)),
            context: null,
            fields: []
        );
    }

    public function getLevelNames(): array
    {
        $names = [];

        foreach (ReceiverLogLevelEnum::cases() as $case) {
            $names[] = new LogLevelNameObject(level: $case->value, name: strtoupper($case->name));
        }

        return $names;
    }

    private function parseTime(string $text): ?int
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

    private function parseLevel(string $level): int
    {
        return match ($level) {
            'DEBUG' => ReceiverLogLevelEnum::Debug->value,
            'INFO'  => ReceiverLogLevelEnum::Info->value,
            'WARN'  => ReceiverLogLevelEnum::Warn->value,
            'ERROR' => ReceiverLogLevelEnum::Error->value,
            default => 0,
        };
    }
}
