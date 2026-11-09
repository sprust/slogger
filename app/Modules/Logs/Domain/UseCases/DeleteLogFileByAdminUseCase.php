<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\UseCases;

use App\Modules\Logs\Domain\Exceptions\LogFileIndexingException;
use App\Modules\Logs\Domain\Exceptions\RequestedLogFileNotFoundException;
use App\Modules\Common\Domain\Exceptions\MutexLockTimeoutException;
use App\Modules\Logs\Domain\Exceptions\LogFileNotDeletableException;
use App\Modules\Logs\Domain\Actions\DeleteLogFileAction;

readonly class DeleteLogFileByAdminUseCase
{
    public function __construct(private DeleteLogFileAction $deleteLogFileAction)
    {
    }

    /**
     * @throws LogFileNotDeletableException
     * @throws LogFileIndexingException
     * @throws RequestedLogFileNotFoundException
     */
    public function handle(string $fileId): bool
    {
        try {
            $deleted = $this->deleteLogFileAction->handle($fileId);
        } catch (MutexLockTimeoutException $exception) {
            throw new LogFileIndexingException(
                message: "Log file [{$fileId}] is being indexed, try again",
                previous: $exception,
            );
        }

        if (!$deleted) {
            throw new RequestedLogFileNotFoundException($fileId);
        }

        return true;
    }
}
