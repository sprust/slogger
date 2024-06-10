<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Parameters\TraceFindStatusesParameters;

interface FindStatusesActionInterface
{
    /**
     * @return string[]
     */
    public function handle(TraceFindStatusesParameters $parameters): array;
}
