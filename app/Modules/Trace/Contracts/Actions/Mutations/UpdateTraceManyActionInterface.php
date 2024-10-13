<?php

namespace App\Modules\Trace\Contracts\Actions\Mutations;

use App\Modules\Trace\Parameters\TraceUpdateParametersList;

interface UpdateTraceManyActionInterface
{
    public function handle(TraceUpdateParametersList $parametersList): int;
}
