<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindMinLoggedAtTracesActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
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
