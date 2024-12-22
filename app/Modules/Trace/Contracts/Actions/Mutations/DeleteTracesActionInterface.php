<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Mutations;

use App\Modules\Trace\Parameters\DeleteTracesParameters;

interface DeleteTracesActionInterface
{
    public function handle(DeleteTracesParameters $parameters): int;
}
