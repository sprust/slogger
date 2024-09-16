<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\TraceStringFieldObject;
use App\Modules\Trace\Domain\Entities\Parameters\TraceFindStatusesParameters;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;

interface FindStatusesActionInterface
{
    /**
     * @return TraceStringFieldObject[]
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexErrorException
     */
    public function handle(TraceFindStatusesParameters $parameters): array;
}
