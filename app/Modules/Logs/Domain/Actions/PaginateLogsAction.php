<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Actions;

use App\Modules\Logs\Contracts\Actions\PaginateLogsActionInterface;
use App\Modules\Logs\Contracts\Repositories\LogRepositoryInterface;
use App\Modules\Logs\Entities\Log\LogsPaginationObject;
use App\Modules\Logs\Parameters\FindLogsParameters;

readonly class PaginateLogsAction implements PaginateLogsActionInterface
{
    public function __construct(
        protected LogRepositoryInterface $logRepository
    ) {
    }

    public function handle(int $page, FindLogsParameters $parameters): LogsPaginationObject
    {
        return $this->logRepository->paginate(
            page: $page,
            perPage: 20,
            parameters: $parameters
        );
    }
}
