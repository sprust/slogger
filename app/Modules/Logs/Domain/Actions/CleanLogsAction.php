<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Actions;

use App\Modules\Common\Domain\Exceptions\MutexLockTimeoutException;
use App\Modules\Common\Domain\Services\Mutex\MutexManagerInterface;
use App\Modules\Logs\Domain\Services\Files\LogFileFinder;
use App\Modules\Logs\Domain\Services\Index\LogIndexMutex;
use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Entities\Index\LogCleanResultObject;
use App\Modules\Logs\Repositories\LogFileRepository;
use App\Modules\Logs\Repositories\LogIndexRepository;
use Illuminate\Support\Carbon;

readonly class CleanLogsAction
{
    private const int WAIT_FOR_LOCK_SEC = 1;

    public function __construct(
        private LogFileFinder $logFileFinder,
        private LogFileRepository $logFileRepository,
        private LogIndexRepository $logIndexRepository,
        private MutexManagerInterface $mutexManager
    ) {
    }

    public function handle(Carbon $now): LogCleanResultObject
    {
        $deletedFiles = [];
        $keptFileIds  = [];

        foreach ($this->logFileFinder->findAll() as $file) {
            if ($this->isExpired($file, $now) && $this->logFileRepository->delete($file->path)) {
                $deletedFiles[] = $file->path;

                continue;
            }

            $keptFileIds[] = $file->id;
        }

        $deletedIndexes = 0;
        $skippedIndexes = 0;

        foreach ($this->logIndexRepository->findFileIds() as $fileId) {
            if (in_array($fileId, $keptFileIds, true)) {
                continue;
            }

            $mutex = new LogIndexMutex(fileId: $fileId, waitForBlockSec: self::WAIT_FOR_LOCK_SEC);

            try {
                $this->mutexManager->lock($mutex);
            } catch (MutexLockTimeoutException) {
                ++$skippedIndexes;

                continue;
            }

            try {
                $this->logIndexRepository->delete($fileId);

                ++$deletedIndexes;
            } finally {
                $this->mutexManager->unlock($mutex);
            }
        }

        return new LogCleanResultObject(
            deletedFiles: $deletedFiles,
            deletedIndexes: $deletedIndexes,
            skippedIndexes: $skippedIndexes
        );
    }

    private function isExpired(LogFileObject $file, Carbon $now): bool
    {
        if ($file->keepDays === null) {
            return false;
        }

        return $file->modifiedAtMs < $now->copy()->subDays($file->keepDays)->getTimestampMs();
    }
}
