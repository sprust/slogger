<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Entities\Entry\LogEntryDetailsObject;
use App\Modules\Logs\Entities\Entry\LogEntryFieldObject;
use App\Modules\Logs\Entities\Formats\LogLevelNameObject;
use App\Modules\Logs\Enums\SconcurJournalLogLevelEnum;

/**
 * The sconcur master journal: `[Y-m-d H:i:s.u] LEVEL [master: pid]: message {context}`, or
 * `[worker: pid group #slot]` for a worker's event or output line. In app.timezone, which is UTC.
 */
readonly class SconcurJournalLogFormat extends AbstractLineLogFormat
{
    private const string LINE_PATTERN = '/^(?:\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\.\d+\] ([A-Z]+) )?[^\n]*$/m';

    private const string ENTRY_PATTERN = ('/^\[[^\]]+\] [A-Z]+ \[(master|worker): (\d+)(?: (?:(\S+) '
        . ')?#(\d+))?\]: (.*)$/');

    public function parseEntry(string $text): LogEntryDetailsObject
    {
        $line = rtrim($text, "\r\n");

        if (preg_match(self::ENTRY_PATTERN, $line, $match) !== 1) {
            return new LogEntryDetailsObject(
                message: $line,
                context: null,
                fields: [],
            );
        }

        [$message, $context] = $this->splitContext($match[5]);

        $worker = null;

        if ($match[1] === 'worker' && $match[4] !== '') {
            $worker = sprintf('%s#%s', $match[3], $match[4]);
        }

        return new LogEntryDetailsObject(
            message: $message,
            context: $context,
            fields: [
                new LogEntryFieldObject(
                    key: 'process',
                    value: $match[1],
                ),
                new LogEntryFieldObject(
                    key: 'pid',
                    value: $match[2],
                ),
                new LogEntryFieldObject(
                    key: 'worker',
                    value: $worker,
                ),
            ],
        );
    }

    public function getLevelNames(): array
    {
        $names = [];

        foreach (SconcurJournalLogLevelEnum::cases() as $case) {
            $names[] = new LogLevelNameObject(
                level: $case->value,
                name: strtoupper($case->name),
            );
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
            zone: null,
        );
    }

    protected function parseLevel(string $text): int
    {
        return match ($text) {
            'INFO'  => SconcurJournalLogLevelEnum::Info->value,
            'WARN'  => SconcurJournalLogLevelEnum::Warn->value,
            'ERROR' => SconcurJournalLogLevelEnum::Error->value,
            default => 0,
        };
    }

    /**
     * The master ends every line with its context as JSON, `[]` when there is none.
     *
     * @return array{string, ?string}
     */
    private function splitContext(string $body): array
    {
        if (str_ends_with($body, ' []')) {
            return [
                substr($body, 0, -3),
                null,
            ];
        }

        $position = strpos($body, ' {');

        while ($position !== false) {
            $json = substr($body, $position + 1);

            if (json_validate($json)) {
                return [
                    substr($body, 0, $position),
                    $json,
                ];
            }

            $position = strpos($body, ' {', $position + 1);
        }

        return [
            $body,
            null,
        ];
    }
}
