<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

use App\Modules\Trace\Domain\Entities\Parameters\TraceUpdateParametersList;

interface UpdateTraceManyActionInterface
{
    public function handle(TraceUpdateParametersList $parametersList): int;
}
