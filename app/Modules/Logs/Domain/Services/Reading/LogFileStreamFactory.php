<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Reading;

use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Entities\Index\LogIndexMetaObject;
use App\Modules\Logs\Enums\LogCursorDirectionEnum;
use App\Modules\Logs\Parameters\LogEntriesFilterParameters;
use App\Modules\Logs\Repositories\LogIndexRepository;

readonly class LogFileStreamFactory
{
    public function __construct(
        private LogIndexRepository $logIndexRepository,
        private LogIndexSearch $logIndexSearch,
        private LogTextReader $logTextReader
    ) {
    }

    public function make(
        LogFileObject $file,
        LogIndexMetaObject $meta,
        LogEntriesFilterParameters $filter,
        LogCursorDirectionEnum $direction,
        ?int $position = null
    ): LogFileStream {
        [$lowEntryNo, $highEntryNo] = $this->findEntryRange($file, $meta, $filter);

        $lanes = [];
        $total = 0;

        foreach ($this->findLevels($meta, $filter) as $level) {
            $count = $level === null ? $meta->entriesCount : ($meta->levelCounts[$level] ?? 0);

            if ($count === 0 || $lowEntryNo > $highEntryNo) {
                continue;
            }

            $low  = $this->findIndex($file, $level, $count, $lowEntryNo);
            $high = $this->findIndex($file, $level, $count, $highEntryNo + 1) - 1;

            if ($low > $high) {
                continue;
            }

            $total += $high - $low + 1;

            if ($position !== null) {
                if ($direction === LogCursorDirectionEnum::Older) {
                    $high = min($high, $this->findIndex($file, $level, $count, $position + 1) - 1);
                } else {
                    $low = max($low, $this->findIndex($file, $level, $count, $position));
                }
            }

            $lanes[] = [
                'level'  => $level,
                'low'    => $low,
                'high'   => $high,
                'next'   => $direction === LogCursorDirectionEnum::Older ? $high : $low,
                'buffer' => [],
            ];
        }

        $searchQuery = $filter->searchQuery;

        return new LogFileStream(
            file: $file,
            logIndexRepository: $this->logIndexRepository,
            logTextReader: $this->logTextReader,
            direction: $direction,
            searchQuery: $searchQuery === null || $searchQuery === '' ? null : $searchQuery,
            maxEntryBytes: max(1, (int) config('module-logs.reading.max_entry_bytes')),
            total: $total,
            lanes: $lanes
        );
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function findEntryRange(LogFileObject $file, LogIndexMetaObject $meta, LogEntriesFilterParameters $filter): array
    {
        $low  = 0;
        $high = $meta->entriesCount - 1;

        if ($filter->fromTime !== null) {
            $low = $this->logIndexSearch->lowerBoundByTime($file->id, $meta->entriesCount, $filter->fromTime);
        }

        if ($filter->toTime !== null) {
            $high = $this->logIndexSearch->lowerBoundByTime($file->id, $meta->entriesCount, $filter->toTime + 1) - 1;
        }

        return [$low, $high];
    }

    /**
     * @return list<int|null>
     */
    private function findLevels(LogIndexMetaObject $meta, LogEntriesFilterParameters $filter): array
    {
        if ($filter->levels === null) {
            return [null];
        }

        $levels = array_values(
            array_unique(
                array_filter(
                    $filter->levels,
                    static fn(int $level): bool => ($meta->levelCounts[$level] ?? 0) > 0
                )
            )
        );

        sort($levels);

        return $levels;
    }

    private function findIndex(LogFileObject $file, ?int $level, int $count, int $entryNo): int
    {
        if ($level === null) {
            return max(0, min($count, $entryNo));
        }

        return $this->logIndexSearch->lowerBoundByEntryNo($file->id, $level, $count, $entryNo);
    }
}
