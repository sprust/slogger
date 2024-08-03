<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

use App\Modules\Trace\Domain\Entities\Parameters\DeleteTracesParameters;

interface DeleteTracesActionInterface
{
    public function handle(DeleteTracesParameters $parameters): int;
}
