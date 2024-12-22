<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Mutations;

use App\Modules\Trace\Parameters\TraceCreateParametersList;

interface CreateTraceManyActionInterface
{
    public function handle(TraceCreateParametersList $parametersList): void;
}
