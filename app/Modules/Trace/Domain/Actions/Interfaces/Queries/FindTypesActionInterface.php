<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\TraceStringFieldObject;
use App\Modules\Trace\Domain\Entities\Parameters\TraceFindTypesParameters;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;

interface FindTypesActionInterface
{
    /**
     * @return TraceStringFieldObject[]
     *
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexNotInitException
     */
    public function handle(TraceFindTypesParameters $parameters): array;
}
