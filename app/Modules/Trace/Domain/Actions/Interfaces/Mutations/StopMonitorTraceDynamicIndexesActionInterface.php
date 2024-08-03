<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

interface StopMonitorTraceDynamicIndexesActionInterface
{
    public function handle(): void;
}
