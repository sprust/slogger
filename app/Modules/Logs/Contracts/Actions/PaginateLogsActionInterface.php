<?php

declare(strict_types=1);

namespace App\Modules\Logs\Contracts\Actions;

use App\Modules\Logs\Entities\Log\LogsPaginationObject;
use App\Modules\Logs\Parameters\FindLogsParameters;

interface PaginateLogsActionInterface
{
    public function handle(int $page, FindLogsParameters $parameters): LogsPaginationObject;
}
