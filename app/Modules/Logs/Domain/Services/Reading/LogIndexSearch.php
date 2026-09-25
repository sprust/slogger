<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Reading;

use App\Modules\Logs\Entities\Index\LogIndexRecordObject;
use App\Modules\Logs\Repositories\LogIndexRepository;
use Closure;

readonly class LogIndexSearch
{
    private const int SCAN_RECORDS = 512;

    public function __construct(
        private LogIndexRepository $logIndexRepository
    ) {
    }

    public function lowerBoundByEntryNo(string $fileId, ?int $level, int $count, int $entryNo): int
    {
        return $this->lowerBound(
            fileId: $fileId,
            level: $level,
            count: $count,
            isBelow: static fn(LogIndexRecordObject $record): bool => $record->entryNo < $entryNo
        );
    }

    public function lowerBoundByTime(string $fileId, ?int $level, int $count, int $time): int
    {
        return $this->lowerBound(
            fileId: $fileId,
            level: $level,
            count: $count,
            isBelow: static fn(LogIndexRecordObject $record): bool => $record->loggedAt < $time
        );
    }

    /**
     * @param Closure(LogIndexRecordObject): bool $isBelow
     */
    private function lowerBound(string $fileId, ?int $level, int $count, Closure $isBelow): int
    {
        $low  = 0;
        $high = $count;

        while ($high - $low > self::SCAN_RECORDS) {
            $middle = intdiv($low + $high, 2);

            $record = $this->logIndexRepository->readRecords($fileId, $level, $middle, 1)[0] ?? null;

            if ($record === null) {
                $high = $middle;
            } elseif ($isBelow($record)) {
                $low = $middle + 1;
            } else {
                $high = $middle;
            }
        }

        foreach ($this->logIndexRepository->readRecords($fileId, $level, $low, $high - $low) as $record) {
            if (!$isBelow($record)) {
                return $low;
            }

            ++$low;
        }

        return $high;
    }
}
