<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Mutations;

use App\Modules\Trace\Parameters\ClearTracesParameters;

interface ClearTracesActionInterface
{
    public function handle(ClearTracesParameters $parameters): int;
}
