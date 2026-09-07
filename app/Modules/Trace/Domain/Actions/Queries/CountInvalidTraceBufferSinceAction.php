<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Repositories\TraceBufferRepository;
use Illuminate\Support\Carbon;

readonly class CountInvalidTraceBufferSinceAction
{
    public function __construct(
        private TraceBufferRepository $bufferRepository
    ) {
    }

    public function handle(Carbon $since): int
    {
        return $this->bufferRepository->countInvalidSince($since);
    }
}
