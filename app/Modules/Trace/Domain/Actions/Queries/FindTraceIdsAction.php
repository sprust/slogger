<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceIdsActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Domain\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Entities\Trace\TraceCollectionNameObjects;
use Illuminate\Support\Carbon;

readonly class FindTraceIdsAction implements FindTraceIdsActionInterface
{
    public function __construct(
        private TraceDynamicIndexInitializer $traceDynamicIndexInitializer,
        private TraceRepositoryInterface $traceRepository
    ) {
    }

    /**
     * @param string[]|null $excludedTypes
     *
     * @throws TraceDynamicIndexErrorException
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexNotInitException
     */
    public function handle(
        int $limit,
        Carbon $loggedAtTo,
        ?string $type = null,
        ?array $excludedTypes = null,
        ?bool $noCleared = null
    ): TraceCollectionNameObjects {
        $this->traceDynamicIndexInitializer->init(
            loggedAtTo: $loggedAtTo,
            types: ($type || $excludedTypes) ? ['stub'] : [],
        );

        return $this->traceRepository->findTraceIds(
            limit: $limit,
            loggedAtTo: $loggedAtTo,
            type: $type,
            excludedTypes: $excludedTypes,
            noCleared: $noCleared,
        );
    }
}
