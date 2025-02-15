<?php

declare(strict_types=1);

namespace App\Modules\Logs\Contracts\Repositories;

use App\Modules\Logs\Entities\Log\LogsPaginationObject;
use App\Modules\Logs\Parameters\CreateLogParameters;
use App\Modules\Logs\Parameters\FindLogsParameters;

interface LogRepositoryInterface
{
    public function create(CreateLogParameters $parameters): string;

    public function paginate(int $page, int $perPage, FindLogsParameters $parameters): LogsPaginationObject;
}
