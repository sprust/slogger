<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use Illuminate\Support\Carbon;

readonly class CancelTraceTreeCacheStateAction
{
    public function __construct(
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
    ) {
    }

    public function handle(string $rootTraceId): ?TraceTreeCacheStateObject
    {
        $updated = $this->traceTreeCacheStateRepository->updateStatus(
            rootTraceId: $rootTraceId,
            status: TraceTreeCacheStateStatusEnum::Canceled,
            finishedAt: Carbon::now(),
        );

        if (!$updated) {
            return null;
        }

        return $this->traceTreeCacheStateRepository->findOneByRootTraceId($rootTraceId);
    }
}
