<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\StopMonitorTraceDynamicIndexesActionInterface;
use App\Modules\Trace\Domain\Services\MonitorTraceDynamicIndexesService;

readonly class StopMonitorTraceDynamicIndexesAction implements StopMonitorTraceDynamicIndexesActionInterface
{
    public function __construct(
        private MonitorTraceDynamicIndexesService $monitorTraceDynamicIndexesService
    ) {
    }

    public function handle(): void
    {
        $this->monitorTraceDynamicIndexesService->setStopFlag(true);
    }
}
