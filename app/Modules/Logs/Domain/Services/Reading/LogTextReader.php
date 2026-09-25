<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Reading;

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
     * @return array<int, string>
     */
    public function read(string $path, array $records, int $maxEntryBytes): array
    {
        if ($records === []) {
            return [];
        }

        $ranges = $this->makeRanges($records, $maxEntryBytes);

        if (count($ranges) === 1) {
            $chunks = [
                $this->logFileRepository->read($path, $ranges[0]['offset'], $ranges[0]['length']),
            ];
        } else {
            $waitGroup = WaitGroup::create(self::CONCURRENCY);

            $keys = [];

            foreach ($ranges as $range) {
                $keys[] = $waitGroup->add(
                    fn(): string => $this->logFileRepository->read($path, $range['offset'], $range['length'])
                );
            }

            $results = $waitGroup->waitResults();

            $chunks = [];

            foreach ($keys as $key) {
                $chunks[] = (string) $results[$key];
            }
        }

        $texts = [];

        foreach ($ranges as $index => $range) {
            foreach ($range['records'] as $record) {
                $texts[$record->entryNo] = substr(
                    $chunks[$index],
                    $record->offset - $range['offset'],
                    min($record->length, $maxEntryBytes)
                );
            }
        }

        return $texts;
    }

    /**
     * @param list<LogIndexRecordObject> $records
     *
     * @return list<array{offset: int, length: int, records: list<LogIndexRecordObject>}>
     */
    private function makeRanges(array $records, int $maxEntryBytes): array
    {
        usort(
            $records,
            static fn(LogIndexRecordObject $left, LogIndexRecordObject $right): int => $left->offset <=> $right->offset
        );

        $ranges  = [];
        $current = null;

        foreach ($records as $record) {
            $end = $record->offset + min($record->length, $maxEntryBytes);

            if (
                $current !== null
                && $record->offset - ($current['offset'] + $current['length']) <= self::MAX_GAP_BYTES
                && $end - $current['offset'] <= self::MAX_RANGE_BYTES
            ) {
                $current['length']    = max($current['length'], $end - $current['offset']);
                $current['records'][] = $record;

                continue;
            }

            if ($current !== null) {
                $ranges[] = $current;
            }

            $current = [
                'offset'  => $record->offset,
                'length'  => $end - $record->offset,
                'records' => [$record],
            ];
        }

        if ($current !== null) {
            $ranges[] = $current;
        }

        return $ranges;
    }
}
