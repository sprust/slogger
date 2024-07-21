<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\TraceItemObjects;
use App\Modules\Trace\Domain\Entities\Parameters\TraceFindParameters;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;

interface FindTracesActionInterface
{
    /**
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexInProcessException
     */
    public function handle(TraceFindParameters $parameters): TraceItemObjects;
}
