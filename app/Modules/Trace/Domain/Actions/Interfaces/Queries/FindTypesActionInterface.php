<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Parameters\TraceFindTypesParameters;

interface FindTypesActionInterface
{
    /**
     * @return string[]
     */
    public function handle(TraceFindTypesParameters $parameters): array;
}
