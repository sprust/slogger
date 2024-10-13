<?php

namespace App\Modules\Trace\Contracts\Actions\Mutations;

interface FlushDynamicIndexesActionInterface
{
    public function handle(): void;
}
