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

    private ?LogIndexRecordObject $head = null;

    private int $scannedRecords = 0;
    private int $scannedBytes   = 0;
    private ?int $lastTime      = null;

    /**
     * @param list<LogFileStreamLane> $lanes
     */
    public function __construct(
        private readonly LogFileObject $file,
        private readonly LogIndexRepository $logIndexRepository,
        private readonly LogTextReader $logTextReader,
        private readonly LogCursorDirectionEnum $direction,
        private readonly ?string $searchQuery,
        private readonly ?int $fromTime,
        private readonly ?int $toTime,
        private readonly int $maxEntryBytes,
        private readonly int $total,
        private readonly int $entriesCount,
        private readonly array $lanes
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

    public function getContinuePosition(): int
    {
        return $this->getPosition()
            ?? ($this->direction === LogCursorDirectionEnum::Older ? -1 : $this->entriesCount);
    }

    public function getDirection(): LogCursorDirectionEnum
    {
        return $this->direction;
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

        if (count($records) === 0) {
            return [];
        }

        $this->scannedRecords += count($records);
        $this->lastTime = $records[count($records) - 1]->loggedAt;

        $records = array_values(
            array_filter(
                $records,
                fn(LogIndexRecordObject $record): bool => ($this->fromTime === null || $record->loggedAt >= $this->fromTime)
                    && ($this->toTime === null || $record->loggedAt <= $this->toTime)
            )
        );

        $texts = [];

        foreach ($this->logTextReader->read($this->file->path, $records, $this->maxEntryBytes) as $entryText) {
            $texts[$entryText->entryNo] = $entryText->text;
        }

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
        if ($this->head === null) {
            $this->head = $this->pickHead();
        }

        return $this->head;
    }

    private function take(): ?LogIndexRecordObject
    {
        $record = $this->peek();

        $this->head = null;

        return $record;
    }

    private function pickHead(): ?LogIndexRecordObject
    {
        $bestLane = null;

        foreach ($this->lanes as $lane) {
            if (count($lane->buffer) === 0) {
                $this->fillLane($lane);
            }

            if (count($lane->buffer) === 0) {
                continue;
            }

            if ($bestLane === null) {
                $bestLane = $lane;

                continue;
            }

            $isBetter = $this->direction === LogCursorDirectionEnum::Older
                ? $lane->buffer[0]->entryNo > $bestLane->buffer[0]->entryNo
                : $lane->buffer[0]->entryNo < $bestLane->buffer[0]->entryNo;

            if ($isBetter) {
                $bestLane = $lane;
            }
        }

        if ($bestLane === null) {
            return null;
        }

        return array_shift($bestLane->buffer);
    }

    private function fillLane(LogFileStreamLane $lane): void
    {
        if ($lane->next < $lane->low || $lane->next > $lane->high) {
            return;
        }

        if ($this->direction === LogCursorDirectionEnum::Older) {
            $from  = max($lane->low, $lane->next - self::BLOCK_RECORDS + 1);
            $count = $lane->next - $from + 1;

            $lane->buffer = array_reverse(
                $this->logIndexRepository->readRecords($this->file->id, $lane->level, $from, $count)
            );

            $lane->next = $from - 1;

            return;
        }

        $count = min(self::BLOCK_RECORDS, $lane->high - $lane->next + 1);

        $lane->buffer = $this->logIndexRepository->readRecords($this->file->id, $lane->level, $lane->next, $count);

        $lane->next += $count;
    }

    private function matches(string $text, string $query): bool
    {
        if (preg_match('/[^\x00-\x7F]/', $query) === 1) {
            return mb_stripos($text, $query) !== false;
        }

        return stripos($text, $query) !== false;
    }
}
