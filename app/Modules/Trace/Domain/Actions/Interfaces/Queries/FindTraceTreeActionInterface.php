<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\Tree\TraceTreeObjects;
use App\Modules\Trace\Domain\Entities\Parameters\TraceFindTreeParameters;
use App\Modules\Trace\Domain\Exceptions\TreeTooLongException;

interface FindTraceTreeActionInterface
{
    /**
     * @throws TreeTooLongException
     */
    public function handle(TraceFindTreeParameters $parameters): TraceTreeObjects;
}
