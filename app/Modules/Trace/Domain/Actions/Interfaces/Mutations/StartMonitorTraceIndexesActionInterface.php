<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

interface StartMonitorTraceIndexesActionInterface
{
    public function handle(): void;
}
