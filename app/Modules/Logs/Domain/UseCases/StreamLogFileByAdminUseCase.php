<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\UseCases;

use App\Modules\Logs\Domain\Exceptions\RequestedLogFileNotFoundException;
use App\Modules\Logs\Domain\Exceptions\LogFileTooLargeException;
use App\Modules\Logs\Entities\File\LogFileDownloadObject;
use App\Modules\Logs\Domain\Actions\StreamLogFileAction;

readonly class StreamLogFileByAdminUseCase
{
    public function __construct(private StreamLogFileAction $streamLogFileAction)
    {
    }

    /**
     * @throws LogFileTooLargeException
     * @throws RequestedLogFileNotFoundException
     */
    public function handle(string $fileId): LogFileDownloadObject
    {
        return $this->streamLogFileAction->handle(
            fileId: $fileId,
        ) ?? throw new RequestedLogFileNotFoundException($fileId);
    }
}
