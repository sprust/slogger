<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

use App\Modules\Trace\Domain\Entities\Parameters\ClearTracesParameters;

interface ClearTracesActionInterface
{
    public function handle(ClearTracesParameters $parameters): int;
}
