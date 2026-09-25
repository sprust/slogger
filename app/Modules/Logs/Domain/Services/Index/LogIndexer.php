<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Index;

use App\Modules\Common\Domain\Services\Mutex\MutexManagerInterface;
use App\Modules\Logs\Domain\Exceptions\LogFileNotFoundException;
use App\Modules\Logs\Domain\Services\Formats\LogFormatInterface;
use App\Modules\Logs\Domain\Services\Formats\LogFormatRegistry;
use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Entities\File\LogFileStatObject;
use App\Modules\Logs\Entities\Index\LogEntryStartObject;
use App\Modules\Logs\Entities\Index\LogIndexMetaObject;
use App\Modules\Logs\Entities\Index\LogIndexRecordObject;
use App\Modules\Logs\Repositories\LogFileRepository;
use App\Modules\Logs\Repositories\LogIndexRepository;

readonly class LogIndexer
{
    public const int VERSION = 1;

    private const int HEAD_BYTES       = 1024;
    private const int MAX_WINDOW_BYTES = 64 * 1024 * 1024;

    public function __construct(
        private LogFileRepository $logFileRepository,
        private LogIndexRepository $logIndexRepository,
        private LogFormatRegistry $logFormatRegistry,
        private MutexManagerInterface $mutexManager
    ) {
    }

    public function ensureFresh(LogFileObject $file, int $timeBudgetMs = 0): LogIndexMetaObject
    {
        $stat = $this->findStat($file);
        $meta = $this->logIndexRepository->findMeta($file->id);

        if ($meta !== null && $this->isFresh($meta, $file, $stat)) {
            return $meta;
        }

        $mutex = new LogIndexMutex($file->id);

        $this->mutexManager->lock($mutex);

        try {
            $stat = $this->findStat($file);
            $meta = $this->logIndexRepository->findMeta($file->id);

            if ($meta !== null && $this->isFresh($meta, $file, $stat)) {
                return $meta;
            }

            return $this->update(
                file: $file,
                stat: $stat,
                meta: $meta,
                timeBudgetMs: $timeBudgetMs
            );
        } finally {
            $this->mutexManager->unlock($mutex);
        }
    }

    private function findStat(LogFileObject $file): LogFileStatObject
    {
        $stat = $this->logFileRepository->stat($file->path);

        if (!$stat->exists) {
            throw new LogFileNotFoundException($file->path);
        }

        return $stat;
    }

    private function isFresh(LogIndexMetaObject $meta, LogFileObject $file, LogFileStatObject $stat): bool
    {
        return $this->isCompatible($meta, $file)
            && $meta->indexedBytes === $stat->sizeBytes;
    }

    private function isCompatible(LogIndexMetaObject $meta, LogFileObject $file): bool
    {
        return $meta->version === self::VERSION
            && $meta->type === $file->type
            && $meta->path === $file->path;
    }

    private function update(
        LogFileObject $file,
        LogFileStatObject $stat,
        ?LogIndexMetaObject $meta,
        int $timeBudgetMs
    ): LogIndexMetaObject {
        $startedAt = hrtime(true);

        $size = $stat->sizeBytes;

        if ($meta !== null && !$this->canContinue($meta, $file, $size)) {
            $meta = null;
        }

        if ($meta === null) {
            $this->logIndexRepository->delete($file->id);

            $entriesCount = 0;
            $levelCounts  = [];
            $position     = 0;
            $lastTime     = null;
        } else {
            $this->dropUncommittedRecords($file->id, $meta);

            $entriesCount = $meta->entriesCount;
            $levelCounts  = $meta->levelCounts;
            $position     = $meta->indexedBytes;
            $lastTime     = null;

            $lastRecord = $entriesCount > 0
                ? $this->logIndexRepository->readRecords($file->id, null, $entriesCount - 1, 1)[0] ?? null
                : null;

            if ($lastRecord !== null) {
                $lastTime = $lastRecord->loggedAt;

                if ($meta->lastEntryOpen) {
                    $this->logIndexRepository->truncateRecords($file->id, null, $entriesCount - 1);
                    $this->logIndexRepository->truncateRecords(
                        fileId: $file->id,
                        level: $lastRecord->level,
                        count: $levelCounts[$lastRecord->level] - 1
                    );

                    --$entriesCount;
                    --$levelCounts[$lastRecord->level];

                    if ($levelCounts[$lastRecord->level] === 0) {
                        unset($levelCounts[$lastRecord->level]);
                    }

                    $position = $lastRecord->offset;
                }
            }
        }

        $headLength = min(self::HEAD_BYTES, $size);
        $headHash   = md5($this->logFileRepository->read($file->path, 0, $headLength));

        $format = $this->logFormatRegistry->get($file->type);

        $baseWindow = max(1, (int) config('module-logs.index.window_bytes'));
        $window     = $baseWindow;

        $meta = $this->makeMeta(
            file: $file,
            indexedBytes: $position,
            lastEntryOpen: false,
            headLength: $headLength,
            headHash: $headHash,
            entriesCount: $entriesCount,
            levelCounts: $levelCounts
        );

        while ($position < $size) {
            $length = min($window, $size - $position);
            $chunk  = $this->logFileRepository->read($file->path, $position, $length);
            $length = strlen($chunk);

            if ($length === 0) {
                break;
            }

            $isEof  = $position + $length >= $size;
            $starts = $this->findStarts($format, $chunk);

            if (!$isEof && count($starts) < 2 && $window < self::MAX_WINDOW_BYTES) {
                $window = min($window * 2, self::MAX_WINDOW_BYTES);

                continue;
            }

            if (!$isEof && count($starts) < 2) {
                $ends     = [$length];
                $starts   = [$starts[0]];
                $consumed = $length;
            } else {
                $ends = [];

                foreach ($starts as $index => $start) {
                    $ends[] = $starts[$index + 1]->offset ?? $length;
                }

                if (!$isEof) {
                    array_pop($starts);
                    array_pop($ends);
                }

                $consumed = $isEof ? $length : $ends[count($ends) - 1];
            }

            $fallbackTime = $lastTime ?? $this->findFirstTime($starts) ?? intdiv($stat->modifiedAtMs, 1000);

            $records = [];

            foreach ($starts as $index => $start) {
                $loggedAt = $start->loggedAt ?? $lastTime ?? $fallbackTime;

                $records[] = new LogIndexRecordObject(
                    entryNo: $entriesCount,
                    offset: $position + $start->offset,
                    length: $ends[$index] - $start->offset,
                    loggedAt: $loggedAt,
                    level: $start->level
                );

                ++$entriesCount;

                $levelCounts[$start->level] = ($levelCounts[$start->level] ?? 0) + 1;

                $lastTime = $loggedAt;
            }

            $this->logIndexRepository->appendRecords($file->id, $records);

            $position += $consumed;
            $window = $baseWindow;

            ksort($levelCounts);

            $meta = $this->makeMeta(
                file: $file,
                indexedBytes: $position,
                lastEntryOpen: $isEof,
                headLength: $headLength,
                headHash: $headHash,
                entriesCount: $entriesCount,
                levelCounts: $levelCounts
            );

            if ($timeBudgetMs > 0 && (hrtime(true) - $startedAt) / 1_000_000 >= $timeBudgetMs) {
                break;
            }
        }

        $this->logIndexRepository->saveMeta($file->id, $meta);

        return $meta;
    }

    private function canContinue(LogIndexMetaObject $meta, LogFileObject $file, int $size): bool
    {
        if (!$this->isCompatible($meta, $file) || $size < $meta->indexedBytes || $size < $meta->headLength) {
            return false;
        }

        $head = $this->logFileRepository->read($file->path, 0, $meta->headLength);

        return md5($head) === $meta->headHash;
    }

    private function dropUncommittedRecords(string $fileId, LogIndexMetaObject $meta): void
    {
        $this->logIndexRepository->truncateRecords($fileId, null, $meta->entriesCount);

        foreach ($this->logIndexRepository->findLevels($fileId) as $level) {
            $this->logIndexRepository->truncateRecords($fileId, $level, $meta->levelCounts[$level] ?? 0);
        }
    }

    /**
     * @return non-empty-list<LogEntryStartObject>
     */
    private function findStarts(LogFormatInterface $format, string $chunk): array
    {
        $starts = $format->findEntryStarts($chunk);

        if ($starts === [] || $starts[0]->offset > 0) {
            array_unshift($starts, new LogEntryStartObject(offset: 0, loggedAt: null, level: 0));
        }

        return $starts;
    }

    /**
     * @param list<LogEntryStartObject> $starts
     */
    private function findFirstTime(array $starts): ?int
    {
        foreach ($starts as $start) {
            if ($start->loggedAt !== null) {
                return $start->loggedAt;
            }
        }

        return null;
    }

    /**
     * @param array<int, int> $levelCounts
     */
    private function makeMeta(
        LogFileObject $file,
        int $indexedBytes,
        bool $lastEntryOpen,
        int $headLength,
        string $headHash,
        int $entriesCount,
        array $levelCounts
    ): LogIndexMetaObject {
        return new LogIndexMetaObject(
            version: self::VERSION,
            path: $file->path,
            type: $file->type,
            indexedBytes: $indexedBytes,
            lastEntryOpen: $lastEntryOpen,
            headLength: $headLength,
            headHash: $headHash,
            entriesCount: $entriesCount,
            levelCounts: $levelCounts
        );
    }
}
