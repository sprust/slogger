<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;

readonly class DeleteTraceTreeCacheStateAction
{
    public function __construct(
        private TraceTreeCacheRepository $traceTreeCacheRepository,
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
    ) {
    }

    public function handle(string $rootTraceId): void
    {
        $this->traceTreeCacheRepository->delete(
            rootTraceId: $rootTraceId
        );
        $this->traceTreeCacheStateRepository->deleteByRootTraceId(
            rootTraceId: $rootTraceId
        );
    }
}
