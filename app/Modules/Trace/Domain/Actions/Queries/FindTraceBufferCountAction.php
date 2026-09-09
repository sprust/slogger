<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Repositories\TraceBufferRepository;

readonly class FindTraceBufferCountAction
{
    public function __construct(
        private TraceBufferRepository $bufferRepository
    ) {
    }

    public function handle(): int
    {
        return $this->bufferRepository->count();
    }
}
