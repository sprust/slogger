<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindMinLoggedAtTracesActionInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;
use Illuminate\Support\Carbon;

readonly class FindMinLoggedAtTracesAction implements FindMinLoggedAtTracesActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository
    ) {
    }

    public function handle(): ?Carbon
    {
        return $this->traceRepository->findMinLoggedAt();
    }
}
