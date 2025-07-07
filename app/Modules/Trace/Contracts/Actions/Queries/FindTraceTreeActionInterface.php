<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeObjects;
use App\Modules\Trace\Parameters\TraceFindTreeParameters;

interface FindTraceTreeActionInterface
{
    public function handle(TraceFindTreeParameters $parameters): TraceTreeObjects;
}
