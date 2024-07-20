<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\StopMonitorTraceIndexesActionInterface;
use App\Modules\Trace\Domain\Services\TraceIndexesService;

readonly class StopMonitorTraceIndexesAction implements StopMonitorTraceIndexesActionInterface
{
    public function __construct(
        private TraceIndexesService $indexesService
    ) {
    }

    public function handle(): void
    {
        $this->indexesService->setStopFlag(true);
    }
}
