<?php

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;
use App\Modules\Trace\Parameters\TraceFindTypesParameters;

interface FindTypesActionInterface
{
    /**
     * @return TraceStringFieldObject[]
     *
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexErrorException
     */
    public function handle(TraceFindTypesParameters $parameters): array;
}
