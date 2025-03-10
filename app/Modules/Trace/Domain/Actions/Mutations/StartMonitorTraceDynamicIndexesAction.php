<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\StartMonitorTraceDynamicIndexesActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Domain\Services\MonitorTraceDynamicIndexesService;
use Throwable;

readonly class StartMonitorTraceDynamicIndexesAction implements StartMonitorTraceDynamicIndexesActionInterface
{
    public function __construct(
        private TraceDynamicIndexRepositoryInterface $traceDynamicIndexRepository,
        private TraceRepositoryInterface $traceRepository,
        private MonitorTraceDynamicIndexesService $monitorTraceDynamicIndexesService
    ) {
    }

    public function handle(): void
    {
        $this->monitorTraceDynamicIndexesService->setStopFlag(false);

        $deletingTimeout = time() + 30;

        while (!$this->monitorTraceDynamicIndexesService->hasRestartFlag()) {
            if ($deletingTimeout < time()) {
                $indexes = $this->traceDynamicIndexRepository->find(
                    limit: 10,
                    inProcess: false,
                    sortByCreatedAtAsc: true,
                    toActualUntilAt: now()
                );

                foreach ($indexes as $index) {
                    if ($index->created) {
                        $this->traceRepository->deleteIndexByName(
                            indexName: $index->indexName,
                            collectionNames: $index->collectionNames
                        );
                    }

                    $this->traceDynamicIndexRepository->deleteById(
                        id: $index->id
                    );
                }

                $deletingTimeout = time() + 30;
            }

            $indexes = $this->traceDynamicIndexRepository->find(
                limit: 10,
                inProcess: true,
                sortByCreatedAtAsc: true,
            );

            if (empty($indexes)) {
                sleep(1);

                continue;
            }

            foreach ($indexes as $index) {
                $exception = null;

                try {
                    $indexCreated = $this->traceRepository->createIndex(
                        name: $index->indexName,
                        collectionNames: $index->collectionNames,
                        fields: $index->fields
                    );
                } catch (Throwable $exception) {
                    $indexCreated = false;
                }

                $this->traceDynamicIndexRepository->updateByName(
                    name: $index->name,
                    inProcess: false,
                    created: $indexCreated,
                    exception: $exception
                );
            }
        }
    }
}
