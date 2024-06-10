<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Parameters\FindTraceIdsParameters;

interface FindTraceIdsActionInterface
{
    /**
     * @return string[]
     */
    public function handle(FindTraceIdsParameters $parameters): array;
}
