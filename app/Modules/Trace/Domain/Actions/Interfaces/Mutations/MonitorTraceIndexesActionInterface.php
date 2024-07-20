<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

interface MonitorTraceIndexesActionInterface
{
    public function handle(): void;
}
