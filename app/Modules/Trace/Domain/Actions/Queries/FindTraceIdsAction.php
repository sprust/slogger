<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceIdsActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Domain\Services\TraceDynamicIndexInitializer;
use Illuminate\Support\Carbon;

readonly class FindTraceIdsAction implements FindTraceIdsActionInterface
{
    public function __construct(
        private TraceDynamicIndexInitializer $traceDynamicIndexInitializer,
        private TraceRepositoryInterface $traceRepository
    ) {
    }

    public function handle(
        int $limit,
        Carbon $loggedAtTo,
        ?string $type = null,
        ?array $excludedTypes = null
    ): array {
        $this->traceDynamicIndexInitializer->init(
            loggedAtTo: $loggedAtTo,
            types: ($type || $excludedTypes) ? ['stub'] : [],
        );

        return $this->traceRepository->findTraceIds(
            limit: $limit,
            loggedAtTo: $loggedAtTo,
            type: $type,
            excludedTypes: $excludedTypes
        );
    }
}
