<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Events\TraceTreeCacheStateChangedEvent;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use App\Modules\Common\Domain\Services\DispatcherInterface;
use Illuminate\Support\Carbon;

readonly class CancelTraceTreeCacheStateAction
{
    public function __construct(
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
        private DispatcherInterface $dispatcher
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

        $state = $this->traceTreeCacheStateRepository->findOneByRootTraceId($rootTraceId);

        // The tab that pressed cancel already has the answer in the response; this is for
        // every other tab watching the same tree.
        if ($state !== null) {
            $this->dispatcher->event(new TraceTreeCacheStateChangedEvent($state));
        }

        return $state;
    }
}
