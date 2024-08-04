<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

interface FlushDynamicIndexesActionInterface
{
    public function handle(): void;
}
