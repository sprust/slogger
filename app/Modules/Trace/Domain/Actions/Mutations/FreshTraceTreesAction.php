<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\FreshTraceTreesActionInterface;
use App\Modules\Trace\Repositories\Services\PeriodicTraceService;

readonly class FreshTraceTreesAction implements FreshTraceTreesActionInterface
{
    public function __construct(
        private PeriodicTraceService $periodicTraceService
    ) {
    }

    public function handle(): void
    {
        $this->periodicTraceService->freshTraceTrees();
    }
}
