<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Actions;

use App\Modules\Common\Domain\Exceptions\MutexLockTimeoutException;
use App\Modules\Common\Domain\Services\Mutex\MutexManagerInterface;
use App\Modules\Logs\Domain\Exceptions\LogFileNotDeletableException;
use App\Modules\Logs\Domain\Services\Files\LogFileFinder;
use App\Modules\Logs\Domain\Services\Index\LogIndexMutex;
use App\Modules\Logs\Repositories\LogFileRepository;
use App\Modules\Logs\Repositories\LogIndexRepository;

readonly class DeleteLogFileAction
{
    private const int WAIT_FOR_LOCK_SEC = 5;

    public function __construct(
        private LogFileFinder $logFileFinder,
        private LogFileRepository $logFileRepository,
        private LogIndexRepository $logIndexRepository,
        private MutexManagerInterface $mutexManager
    ) {
    }

    /**
     * False when there is no such file among the sources.
     *
     * @throws LogFileNotDeletableException
     * @throws MutexLockTimeoutException
     */
    public function handle(string $fileId): bool
    {
        $file = $this->logFileFinder->findById($fileId);

        if ($file === null) {
            return false;
        }

        if (!$file->deletable) {
            throw new LogFileNotDeletableException($file->name);
        }

        // The index is dropped with the file, under the lock an indexer takes.
        $mutex = new LogIndexMutex(fileId: $file->id, waitForBlockSec: self::WAIT_FOR_LOCK_SEC);

        $this->mutexManager->lock($mutex);

        try {
            $this->logFileRepository->delete($file->path);
            $this->logIndexRepository->delete($file->id);
        } finally {
            $this->mutexManager->unlock($mutex);
        }

        return true;
    }
}
