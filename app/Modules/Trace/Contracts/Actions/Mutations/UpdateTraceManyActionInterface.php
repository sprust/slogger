<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Mutations;

use App\Modules\Trace\Parameters\TraceUpdateParametersList;

interface UpdateTraceManyActionInterface
{
    public function handle(TraceUpdateParametersList $parametersList): void;
}
