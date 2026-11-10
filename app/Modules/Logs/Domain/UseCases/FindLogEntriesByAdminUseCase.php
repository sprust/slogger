<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\UseCases;

use App\Modules\Logs\Domain\Exceptions\LogCursorInvalidException;
use App\Modules\Logs\Entities\Entry\LogEntriesPageObject;
use App\Modules\Logs\Parameters\FindLogEntriesParameters;
use App\Modules\Logs\Domain\Actions\FindLogEntriesAction;

readonly class FindLogEntriesByAdminUseCase
{
    public function __construct(private FindLogEntriesAction $findLogEntriesAction)
    {
    }

    /**
     * @throws LogCursorInvalidException
     */
    public function handle(FindLogEntriesParameters $parameters): LogEntriesPageObject
    {
        return $this->findLogEntriesAction->handle($parameters);
    }
}
