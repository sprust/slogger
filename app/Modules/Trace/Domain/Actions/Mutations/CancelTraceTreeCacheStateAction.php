<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Events\TraceTreeCacheStateChangedEvent;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;

readonly class CancelTraceTreeCacheStateAction
{
    public function __construct(
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
        private Dispatcher $events,
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
            $this->events->dispatch(new TraceTreeCacheStateChangedEvent($state));
        }

        return $state;
    }
}
