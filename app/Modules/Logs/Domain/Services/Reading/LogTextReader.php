<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Reading;

use App\Modules\Logs\Entities\Entry\LogEntryTextObject;
use App\Modules\Logs\Entities\Entry\LogTextRangeObject;
use App\Modules\Logs\Entities\Index\LogIndexRecordObject;
use App\Modules\Logs\Repositories\LogFileRepository;
use SConcur\WaitGroup;

readonly class LogTextReader
{
    private const int MAX_GAP_BYTES   = 64 * 1024;
    private const int MAX_RANGE_BYTES = 8 * 1024 * 1024;
    private const int CONCURRENCY     = 8;

    public function __construct(
        private LogFileRepository $logFileRepository
    ) {
    }

    /**
     * @param list<LogIndexRecordObject> $records
     *
     * @return list<LogEntryTextObject>
     */
    public function read(string $path, array $records, int $maxEntryBytes): array
    {
        if (count($records) === 0) {
            return [];
        }

        $ranges = $this->makeRanges($records, $maxEntryBytes);

        $chunks = $this->readRanges($path, $ranges);

        $texts = [];

        foreach ($ranges as $index => $range) {
            foreach ($range->records as $record) {
                $texts[] = new LogEntryTextObject(
                    entryNo: $record->entryNo,
                    text: substr(
                        $chunks[$index],
                        $record->offset - $range->offset,
                        min($record->length, $maxEntryBytes)
                    )
                );
            }
        }

        return $texts;
    }

    /**
     * @param list<LogTextRangeObject> $ranges
     *
     * @return list<string>
     */
    private function readRanges(string $path, array $ranges): array
    {
        if (count($ranges) === 1) {
            return [
                $this->logFileRepository->read($path, $ranges[0]->offset, $ranges[0]->length),
            ];
        }

        $waitGroup = WaitGroup::create(self::CONCURRENCY);

        $keys = [];

        foreach ($ranges as $range) {
            $keys[] = $waitGroup->add(
                fn(): string => $this->logFileRepository->read($path, $range->offset, $range->length)
            );
        }

        $results = $waitGroup->waitResults();

        $chunks = [];

        foreach ($keys as $key) {
            $chunks[] = (string) $results[$key];
        }

        return $chunks;
    }

    /**
     * @param list<LogIndexRecordObject> $records
     *
     * @return list<LogTextRangeObject>
     */
    private function makeRanges(array $records, int $maxEntryBytes): array
    {
        usort(
            $records,
            static fn(LogIndexRecordObject $left, LogIndexRecordObject $right): int => $left->offset <=> $right->offset
        );

        $ranges = [];

        $rangeOffset  = $records[0]->offset;
        $rangeEnd     = $rangeOffset;
        $rangeRecords = [];

        foreach ($records as $record) {
            $end = $record->offset + min($record->length, $maxEntryBytes);

            $fits = count($rangeRecords) > 0
                && $record->offset - $rangeEnd <= self::MAX_GAP_BYTES
                && $end - $rangeOffset <= self::MAX_RANGE_BYTES;

            if (count($rangeRecords) > 0 && !$fits) {
                $ranges[] = new LogTextRangeObject(
                    offset: $rangeOffset,
                    length: $rangeEnd - $rangeOffset,
                    records: $rangeRecords
                );

                $rangeOffset  = $record->offset;
                $rangeEnd     = $record->offset;
                $rangeRecords = [];
            }

            $rangeRecords[] = $record;

            $rangeEnd = max($rangeEnd, $end);
        }

        $ranges[] = new LogTextRangeObject(
            offset: $rangeOffset,
            length: $rangeEnd - $rangeOffset,
            records: $rangeRecords
        );

        return $ranges;
    }
}
