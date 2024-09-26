<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceIdsActionInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\Trace\Repositories\Services\TraceDynamicIndexInitializer;
use Illuminate\Support\Carbon;

readonly class FindTraceIdsAction implements FindTraceIdsActionInterface
{
    private int $maxPerPage;

    public function __construct(
        private TraceDynamicIndexInitializer $traceDynamicIndexInitializer,
        private TraceRepositoryInterface $traceRepository
    ) {
        $this->maxPerPage = 3000;
    }

    public function handle(
        int $page,
        int $perPage,
        Carbon $loggedAtTo,
        ?string $type = null,
        ?array $excludedTypes = null
    ): array {
        $this->traceDynamicIndexInitializer->init(
            loggedAtTo: $loggedAtTo,
            types: ($type || $excludedTypes) ? ['stub'] : [],
        );

        $perPage = min($perPage ?: $this->maxPerPage, $this->maxPerPage);

        return $this->traceRepository->findIds(
            page: $page,
            perPage: $perPage,
            loggedAtTo: $loggedAtTo,
            type: $type,
            excludedTypes: $excludedTypes
        );
    }
}
