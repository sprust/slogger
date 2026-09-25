<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Reading;

use App\Modules\Logs\Entities\Entry\LogEntryObject;
use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Entities\Index\LogIndexRecordObject;
use App\Modules\Logs\Enums\LogCursorDirectionEnum;
use App\Modules\Logs\Repositories\LogIndexRepository;

class LogFileStream
{
    private const int BLOCK_RECORDS = 500;

    /**
     * @var list<LogIndexRecordObject>
     */
    private array $buffer = [];

    private int $scannedRecords = 0;
    private int $scannedBytes   = 0;
    private ?int $lastTime      = null;

    /**
     * @param list<array{level: int|null, low: int, high: int, next: int, buffer: list<LogIndexRecordObject>}> $lanes
     */
    public function __construct(
        private readonly LogFileObject $file,
        private readonly LogIndexRepository $logIndexRepository,
        private readonly LogTextReader $logTextReader,
        private readonly LogCursorDirectionEnum $direction,
        private readonly ?string $searchQuery,
        private readonly int $maxEntryBytes,
        private readonly int $total,
        private array $lanes
    ) {
    }

    public function getFile(): LogFileObject
    {
        return $this->file;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getScannedRecords(): int
    {
        return $this->scannedRecords;
    }

    public function getScannedBytes(): int
    {
        return $this->scannedBytes;
    }

    public function getLastTime(): ?int
    {
        return $this->lastTime;
    }

    public function isExhausted(): bool
    {
        return $this->peek() === null;
    }

    public function getPosition(): ?int
    {
        return $this->peek()?->entryNo;
    }

    public function getNextTime(): ?int
    {
        return $this->peek()?->loggedAt;
    }

    /**
     * @return list<LogEntryObject>
     */
    public function next(int $maxRecords = self::BLOCK_RECORDS): array
    {
        $records = [];

        while (count($records) < $maxRecords && ($record = $this->take()) !== null) {
            $records[] = $record;
        }

        if ($records === []) {
            return [];
        }

        $this->scannedRecords += count($records);
        $this->lastTime = $records[count($records) - 1]->loggedAt;

        $texts = $this->logTextReader->read($this->file->path, $records, $this->maxEntryBytes);

        $entries = [];

        foreach ($records as $record) {
            $text = $texts[$record->entryNo] ?? '';

            $this->scannedBytes += strlen($text);

            if ($this->searchQuery !== null && !$this->matches($text, $this->searchQuery)) {
                continue;
            }

            $entries[] = new LogEntryObject(
                fileId: $this->file->id,
                entryNo: $record->entryNo,
                loggedAt: $record->loggedAt,
                level: $record->level,
                text: $text,
                truncated: $record->length > $this->maxEntryBytes
            );
        }

        return $entries;
    }

    private function peek(): ?LogIndexRecordObject
    {
        if ($this->buffer === []) {
            $record = $this->pickHead();

            if ($record === null) {
                return null;
            }

            $this->buffer[] = $record;
        }

        return $this->buffer[0];
    }

    private function take(): ?LogIndexRecordObject
    {
        if ($this->peek() === null) {
            return null;
        }

        return array_shift($this->buffer);
    }

    private function pickHead(): ?LogIndexRecordObject
    {
        $bestLane = null;

        foreach ($this->lanes as $index => $lane) {
            if ($this->lanes[$index]['buffer'] === []) {
                $this->fillLane($index);
            }

            $head = $this->lanes[$index]['buffer'][0] ?? null;

            if ($head === null) {
                continue;
            }

            if ($bestLane === null) {
                $bestLane = $index;

                continue;
            }

            $best = $this->lanes[$bestLane]['buffer'][0];

            $isBetter = $this->direction === LogCursorDirectionEnum::Older
                ? $head->entryNo > $best->entryNo
                : $head->entryNo < $best->entryNo;

            if ($isBetter) {
                $bestLane = $index;
            }
        }

        if ($bestLane === null) {
            return null;
        }

        $lane = $this->lanes[$bestLane];

        $record = array_shift($lane['buffer']);

        $this->lanes[$bestLane] = $lane;

        return $record;
    }

    private function fillLane(int $index): void
    {
        $lane = $this->lanes[$index];

        if ($lane['next'] < $lane['low'] || $lane['next'] > $lane['high']) {
            return;
        }

        if ($this->direction === LogCursorDirectionEnum::Older) {
            $from  = max($lane['low'], $lane['next'] - self::BLOCK_RECORDS + 1);
            $count = $lane['next'] - $from + 1;

            $records = array_reverse(
                $this->logIndexRepository->readRecords($this->file->id, $lane['level'], $from, $count)
            );

            $lane['next'] = $from - 1;
        } else {
            $count = min(self::BLOCK_RECORDS, $lane['high'] - $lane['next'] + 1);

            $records = $this->logIndexRepository->readRecords($this->file->id, $lane['level'], $lane['next'], $count);

            $lane['next'] += $count;
        }

        $lane['buffer'] = $records;

        $this->lanes[$index] = $lane;
    }

    private function matches(string $text, string $query): bool
    {
        if (preg_match('/[^\x00-\x7F]/', $query) === 1) {
            return mb_stripos($text, $query) !== false;
        }

        return stripos($text, $query) !== false;
    }
}
