<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;

readonly class IsShouldContinueBuildTraceTreeCacheAction
{
    public function __construct(
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
    ) {
    }

    public function handle(string $rootTraceId, string $version): bool
    {
        $state = $this->traceTreeCacheStateRepository->findOneByRootTraceId($rootTraceId);

        return $state !== null
            && $state->status === TraceTreeCacheStateStatusEnum::InProcess
            && $state->version === $version;
    }
}
