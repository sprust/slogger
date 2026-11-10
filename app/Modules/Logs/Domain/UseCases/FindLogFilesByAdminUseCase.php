<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\UseCases;

use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Domain\Actions\FindLogFilesAction;

readonly class FindLogFilesByAdminUseCase
{
    public function __construct(private FindLogFilesAction $findLogFilesAction)
    {
    }

    /**
     * @return list<LogFileObject>
     */
    public function handle(): array
    {
        return $this->findLogFilesAction->handle();
    }
}
