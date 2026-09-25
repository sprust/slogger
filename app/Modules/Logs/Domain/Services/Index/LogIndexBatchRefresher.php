<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Index;

use App\Modules\Common\Domain\Exceptions\MutexLockTimeoutException;
use App\Modules\Logs\Domain\Exceptions\LogFileNotFoundException;
use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Entities\Index\LogFileIndexObject;
use App\Modules\Logs\Entities\Index\LogIndexBatchObject;
use App\Modules\Logs\Entities\Index\LogIndexMetaObject;
use SConcur\WaitGroup;

readonly class LogIndexBatchRefresher
{
    public function __construct(
        private LogIndexer $logIndexer
    ) {
    }

    /**
     * @param list<LogFileObject> $files
     */
    public function refresh(array $files, int $timeBudgetMs, int $concurrency): LogIndexBatchObject
    {
        if (count($files) === 0) {
            return new LogIndexBatchObject(
                files: [],
                missingFileIds: [],
                indexing: false,
                indexedBytes: 0,
                totalBytes: 0
            );
        }

        $waitForLockSec = max(1, intdiv($timeBudgetMs, 1000));

        $missingFileIds = [];

        $waitGroup = WaitGroup::create(max(1, $concurrency));

        $keys = [];

        foreach ($files as $file) {
            $keys[] = $waitGroup->add(
                function () use ($file, $timeBudgetMs, $waitForLockSec, &$missingFileIds): ?LogIndexMetaObject {
                    try {
                        return $this->logIndexer->ensureFresh(
                            file: $file,
                            timeBudgetMs: $timeBudgetMs,
                            waitForLockSec: $waitForLockSec
                        );
                    } catch (LogFileNotFoundException) {
                        $missingFileIds[] = $file->id;
                    } catch (MutexLockTimeoutException) {
                        return null;
                    }

                    return null;
                }
            );
        }

        $results = $waitGroup->waitResults();

        $indexed      = [];
        $indexing     = false;
        $indexedBytes = 0;
        $totalBytes   = 0;

        foreach ($files as $index => $file) {
            if (in_array($file->id, $missingFileIds, true)) {
                continue;
            }

            $totalBytes += $file->sizeBytes;

            $meta = $results[$keys[$index]] ?? null;

            if (!$meta instanceof LogIndexMetaObject) {
                $indexing = true;

                continue;
            }

            $indexed[] = new LogFileIndexObject(file: $file, meta: $meta);

            $indexedBytes += min($meta->indexedBytes, $file->sizeBytes);

            if ($meta->indexedBytes < $file->sizeBytes) {
                $indexing = true;
            }
        }

        return new LogIndexBatchObject(
            files: $indexed,
            missingFileIds: $missingFileIds,
            indexing: $indexing,
            indexedBytes: $indexedBytes,
            totalBytes: $totalBytes
        );
    }
}
