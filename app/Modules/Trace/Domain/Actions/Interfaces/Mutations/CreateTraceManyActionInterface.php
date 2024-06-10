<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

use App\Modules\Trace\Domain\Entities\Parameters\TraceCreateParametersList;

interface CreateTraceManyActionInterface
{
    public function handle(TraceCreateParametersList $parametersList): void;
}
