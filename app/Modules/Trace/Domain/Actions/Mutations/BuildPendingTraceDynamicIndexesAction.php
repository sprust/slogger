<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Events\TraceDynamicIndexBuiltEvent;
use App\Modules\Trace\Repositories\TraceDynamicIndexRepository;
use App\Modules\Trace\Repositories\TraceRepository;
use Illuminate\Contracts\Events\Dispatcher;
use SConcur\Exceptions\CoroutineTimeoutException;
use SConcur\Exceptions\FlowStoppedException;
use Throwable;

/**
 * Builds one batch of the dynamic indexes that were requested and are still pending.
 *
 * One pass, not a loop: the schedule belongs to whoever calls this. An index that fails
 * to build is recorded as failed rather than retried here, which is what keeps a broken
 * index from holding up the batch behind it.
 */
readonly class BuildPendingTraceDynamicIndexesAction
{
    public function __construct(
        private TraceDynamicIndexRepository $traceDynamicIndexRepository,
        private TraceRepository $traceRepository,
        // Injected rather than reached through the event() helper: this action is unit
        // tested without a container, and a helper would need one.
        private Dispatcher $events,
    ) {
    }

    /**
     * @return int how many indexes this pass handled
     */
    public function handle(int $limit = 10): int
    {
        $indexes = $this->traceDynamicIndexRepository->find(
            limit: $limit,
            inProcess: true,
        );

        foreach ($indexes as $index) {
            $exception = null;

            try {
                $indexCreated = $this->traceRepository->createIndex(
                    name: $index->indexName,
                    collectionNames: $index->collectionNames,
                    fields: $index->fields
                );
            } catch (FlowStoppedException | CoroutineTimeoutException $exception) {
                // The runtime is unwinding this coroutine — the pool's shutdown deadline
                // or a timeout. Recording a failure here would need another repository
                // call on a coroutine that has nothing left to await on, and would brand
                // an index whose build the server may well have finished as failed, with
                // a shutdown as its stated cause. Left in process, it is picked up whole
                // on the next pass.
                throw $exception;
            } catch (Throwable $exception) {
                $indexCreated = false;
            }

            // Always written, whichever way the build went: an index left with
            // inProcess true is picked up again on every later pass, for ever.
            $this->traceDynamicIndexRepository->updateByName(
                name: $index->name,
                inProcess: false,
                created: $indexCreated,
                exception: $exception
            );

            // Raised here and not in the shutdown branch above, which deliberately leaves
            // the index in process: this is the one place an index stops being pending,
            // and somebody is very likely blocked on it with a 412.
            $this->events->dispatch(
                new TraceDynamicIndexBuiltEvent(
                    indexId: $index->id,
                    created: $indexCreated,
                    error: $exception
                        ? $exception::class . ": {$exception->getMessage()}"
                        : null,
                )
            );
        }

        return count($indexes);
    }
}
