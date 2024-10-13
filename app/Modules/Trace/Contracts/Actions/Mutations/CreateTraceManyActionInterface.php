<?php

namespace App\Modules\Trace\Contracts\Actions\Mutations;

use App\Modules\Trace\Parameters\TraceCreateParametersList;

interface CreateTraceManyActionInterface
{
    public function handle(TraceCreateParametersList $parametersList): void;
}
