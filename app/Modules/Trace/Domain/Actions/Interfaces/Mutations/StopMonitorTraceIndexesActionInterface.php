<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

interface StopMonitorTraceIndexesActionInterface
{
    public function handle(): void;
}
