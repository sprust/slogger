<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Entities\Index\LogEntryStartObject;

abstract readonly class AbstractLineLogFormat implements LogFormatInterface
{
    abstract protected function getLinePattern(): string;

    abstract protected function parseTime(string $text): ?int;

    abstract protected function parseLevel(string $text): int;

    public function __construct(
        protected LogTimeParser $timeParser
    ) {
    }

    public function findEntryStarts(string $chunk): array
    {
        preg_match_all($this->getLinePattern(), $chunk, $matches);

        $starts = [];

        $offset   = 0;
        $lastText = null;
        $lastTime = null;

        foreach ($matches[0] as $index => $line) {
            $lineLength = strlen($line);

            if ($lineLength > 0 && trim($line) !== '') {
                $timeText = $matches[1][$index];

                if ($timeText === '') {
                    $starts[] = new LogEntryStartObject(offset: $offset, loggedAt: null, level: 0);
                } else {
                    if ($timeText !== $lastText) {
                        $lastText = $timeText;
                        $lastTime = $this->parseTime($timeText);
                    }

                    $starts[] = new LogEntryStartObject(
                        offset: $offset,
                        loggedAt: $lastTime,
                        level: $this->parseLevel($matches[2][$index])
                    );
                }
            }

            $offset += $lineLength + 1;
        }

        return $starts;
    }
}
