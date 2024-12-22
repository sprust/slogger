<?php

namespace App\Modules\Logs\Domain\Actions;

use App\Modules\Logs\Entities\Log\LogsPaginationObject;
use App\Modules\Logs\Parameters\FindLogsParameters;
use App\Modules\Logs\Repositories\LogRepository;

readonly class PaginateLogsAction
{
    public function __construct(
        protected LogRepository $logRepository
    ) {
    }

    public function handle(int $page, FindLogsParameters $parameters): LogsPaginationObject
    {
        return $this->logRepository->paginate(
            page: $page,
            perPage: 30,
            parameters: $parameters
        );
    }
}
