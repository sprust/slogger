<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\StartMonitorTraceIndexesActionInterface;
use App\Modules\Trace\Domain\Services\TraceIndexesService;
use App\Modules\Trace\Repositories\Interfaces\TraceIndexRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;

readonly class StartMonitorTraceIndexesAction implements StartMonitorTraceIndexesActionInterface
{
    public function __construct(
        private TraceIndexRepositoryInterface $traceIndexRepository,
        private TraceRepositoryInterface $traceRepository,
        private TraceIndexesService $indexesService
    ) {
    }

    public function handle(): void
    {
        $this->indexesService->setStopFlag(false);

        $deletingTimeout = time() + 30;

        while (!$this->indexesService->hasRestartFlag()) {
            if ($deletingTimeout < time()) {
                $indexes = $this->traceIndexRepository->find(
                    limit: 10,
                    inProcess: false,
                    sortByCreatedAtAsc: true,
                    toActualUntilAt: now()
                );

                foreach ($indexes as $index) {
                    if ($index->created) {
                        $this->traceRepository->deleteIndexByName(
                            name: $index->name
                        );
                    }

                    $this->traceIndexRepository->deleteByName(
                        name: $index->name
                    );
                }

                $deletingTimeout = time() + 30;
            }

            $indexes = $this->traceIndexRepository->find(
                limit: 10,
                inProcess: true,
                sortByCreatedAtAsc: true,
            );

            if (empty($indexes)) {
                sleep(1);

                continue;
            }

            foreach ($indexes as $index) {
                $indexCreated = $this->traceRepository->createIndex(
                    name: $index->name,
                    fields: $index->fields
                );

                $this->traceIndexRepository->updateByName(
                    name: $index->name,
                    inProcess: false,
                    created: $indexCreated
                );
            }
        }
    }
}
