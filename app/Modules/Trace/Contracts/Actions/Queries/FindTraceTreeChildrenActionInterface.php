<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeChildrenObjects;
use App\Modules\Trace\Parameters\TraceFindTreeChildrenParameters;

interface FindTraceTreeChildrenActionInterface
{
    public function handle(TraceFindTreeChildrenParameters $parameters): TraceTreeChildrenObjects;
}
