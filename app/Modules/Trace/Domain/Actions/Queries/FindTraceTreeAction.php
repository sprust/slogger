<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Events\TraceTreeCacheBuildRequestedEvent;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeResultObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;
use Illuminate\Support\Str;

readonly class FindTraceTreeAction
{
    public function __construct(
        private TraceRepository $traceRepository,
        private TraceTreeRepository $traceTreeRepository,
        private TraceTreeCacheRepository $traceTreeCacheRepository,
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
    ) {
    }

    public function handle(string $traceId, bool $fresh, bool $isChild): ?TraceTreeResultObject
    {
        $rootTraceId = $isChild
            ? $traceId
            : $this->traceTreeRepository->findParentTraceId(
                traceId: $traceId
            );

        if (!$rootTraceId) {
            return null;
        }

        if ($this->traceRepository->findOneDetailByTraceId($rootTraceId) === null) {
            return null;
        }

        $state = $this->traceTreeCacheStateRepository->findOneByRootTraceId(
            rootTraceId: $rootTraceId
        );

        if ($fresh || $state === null) {
            $state = $this->traceTreeCacheStateRepository->reset(
                rootTraceId: $rootTraceId,
                version: (string) Str::ulid(),
                status: TraceTreeCacheStateStatusEnum::InProcess,
            );

            event(
                new TraceTreeCacheBuildRequestedEvent(
                    rootTraceId: $state->rootTraceId,
                    version: $state->version,
                )
            );

            return new TraceTreeResultObject(
                state: $state,
                items: null,
            );
        }

        if ($state->status === TraceTreeCacheStateStatusEnum::Finished) {
            if ($state->count === 0) {
                $state = $this->traceTreeCacheStateRepository->saveFinished(
                    rootTraceId: $rootTraceId,
                    version: $state->version,
                    count: $this->traceTreeCacheRepository->findCount(
                        rootTraceId: $rootTraceId
                    ),
                );
            }

            return new TraceTreeResultObject(
                state: $state,
                items: $this->traceTreeCacheRepository->findMany(
                    rootTraceId: $rootTraceId
                ),
            );
        }

        return new TraceTreeResultObject(
            state: $state,
            items: null,
        );
    }
}
