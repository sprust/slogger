<?php

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Domain\Exceptions\TreeTooLongException;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeObjects;
use App\Modules\Trace\Parameters\TraceFindTreeParameters;

interface FindTraceTreeActionInterface
{
    /**
     * @throws TreeTooLongException
     */
    public function handle(TraceFindTreeParameters $parameters): TraceTreeObjects;
}
