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

    /**
     * Both ends, and the upper one is the moment the caller is reporting for.
     *
     * An open-ended count would take in whatever arrived while the query was being made,
     * and the next window — which starts at that same reported moment — would take those
     * documents in again.
     */
    public function handle(Carbon $since, Carbon $until): int
    {
        return $this->bufferRepository->countInvalidBetween($since, $until);
    }
}
