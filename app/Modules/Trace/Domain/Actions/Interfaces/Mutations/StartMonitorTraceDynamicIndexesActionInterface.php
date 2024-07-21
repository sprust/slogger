<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

interface StartMonitorTraceDynamicIndexesActionInterface
{
    public function handle(): void;
}
