<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Actions;

use App\Modules\Logs\Domain\Exceptions\LogFileTooLargeException;
use App\Modules\Logs\Domain\Services\Files\LogFileFinder;
use App\Modules\Logs\Entities\File\LogFileDownloadObject;
use App\Modules\Logs\Repositories\LogFileRepository;

readonly class StreamLogFileAction
{
    public function __construct(
        private LogFileFinder $logFileFinder,
        private LogFileRepository $logFileRepository
    ) {
    }

    /**
     * @throws LogFileTooLargeException
     */
    public function handle(string $fileId): ?LogFileDownloadObject
    {
        $file = $this->logFileFinder->findById($fileId);

        if ($file === null) {
            return null;
        }

        $maxBytes = (int) config('module-logs.download.max_bytes');

        if ($file->sizeBytes > $maxBytes) {
            throw new LogFileTooLargeException(name: $file->name, sizeBytes: $file->sizeBytes, maxBytes: $maxBytes);
        }

        return new LogFileDownloadObject(
            file: $file,
            chunks: $this->logFileRepository->readChunks($file->path)
        );
    }
}
